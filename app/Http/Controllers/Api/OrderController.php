<?php

namespace App\Http\Controllers\Api;

use App\Events\MobileOrderReceived;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ShopSetting;
use App\Models\User;
use App\Services\CheckoutService;
use App\Support\Decimal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    /**
     * Create a new order from mobile app (customer checkout).
     * Accepts multipart/form-data with a required QRIS payment_proof image.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:qris',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant' => 'nullable|in:base,lite',
            'items.*.quantity' => 'required|integer|min:1|max:2147483647',
            'payment_proof' => 'required|image|mimes:jpg,jpeg,png,webp|max:3072',
        ], [
            'items.required' => 'Pesanan tidak boleh kosong.',
            'payment_method.in' => 'Pembayaran pesanan mobile hanya mendukung QRIS.',
            'payment_proof.required' => 'Bukti pembayaran wajib diunggah untuk pembayaran QRIS.',
            'payment_proof.max' => 'Ukuran bukti pembayaran maksimal 3MB.',
        ]);

        abort_unless($request->user()->isPengguna(), 403);
        $service = app(CheckoutService::class);
        $proofHash = hash_file('sha256', $request->file('payment_proof')->getRealPath());
        $items = array_map(fn ($item) => ['product_id' => (int) $item['product_id'],
            'variant' => $item['variant'] ?? null, 'quantity' => (int) $item['quantity']], $validated['items']);
        usort($items, fn ($a, $b) => [$a['product_id'], $a['variant'], $a['quantity']] <=> [$b['product_id'], $b['variant'], $b['quantity']]);
        $fingerprint = hash('sha256', json_encode($items, JSON_THROW_ON_ERROR));
        $paymentProofPath = $request->file('payment_proof')->store('payment_proofs', 'local');
        if (! $paymentProofPath) {
            throw ValidationException::withMessages(['payment_proof' => 'Bukti pembayaran gagal disimpan.']);
        }
        try {
            $order = DB::transaction(function () use ($validated, $request, $service, $paymentProofPath, $proofHash, $fingerprint) {
                $customer = User::query()->lockForUpdate()->findOrFail($request->user()->id);
                abort_unless($customer->is_active && $customer->isPengguna(), 403);
                $existing = Order::where('user_id', $customer->id)->where('payment_proof_hash', $proofHash)->first();
                if ($existing) {
                    if (! hash_equals((string) $existing->checkout_fingerprint, $fingerprint)) {
                        throw ValidationException::withMessages(['payment_proof' => 'Bukti pembayaran ini sudah terhubung dengan pesanan lain.']);
                    }

                    return $existing;
                }
                $cart = $service->prepare($validated['items']);
                $total = $cart['total_units'];
                $service->lockIngredients($cart['requirements']);
                $order = Order::create([
                    'order_number' => Order::generateOrderNumber(),
                    'customer_name' => $request->user()->name, 'user_id' => $request->user()->id,
                    'payment_method' => $validated['payment_method'], 'total' => Decimal::value($total),
                    'cash_received' => null, 'change_amount' => null,
                    'payment_proof' => $paymentProofPath, 'status' => 'menunggu_verifikasi',
                    'paid_at' => null, 'payment_proof_hash' => $proofHash, 'checkout_fingerprint' => $fingerprint,
                ]);
                $service->storeItems($order, $cart['items']);

                return $order;
            }, 3);
        } catch (\Throwable $exception) {
            $this->discardUnusedProof($paymentProofPath);
            throw $exception;
        }

        if ($order->payment_proof !== $paymentProofPath) {
            $this->discardUnusedProof($paymentProofPath);
        }
        $order->load('items');
        // Replays must not announce another sale or turn notification failure into checkout failure.
        if ($order->wasRecentlyCreated) {
            try {
                MobileOrderReceived::dispatch($order);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return response()->json([
            'success' => true,
            'order' => $this->formatOrder($order),
        ], 201);
    }

    /** Cleanup failure must not hide a committed checkout or its original error. */
    private function discardUnusedProof(string $path): void
    {
        try {
            Storage::disk('local')->delete($path);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Get all orders for the logged-in customer (grouped by status for tracking).
     */
    public function active(Request $request)
    {
        $orders = Order::with('items')
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['menunggu_verifikasi', 'antrian_baru', 'sedang_dibuat', 'selesai'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders->map(fn (Order $order) => $this->formatOrder($order)),
        ]);
    }

    /**
     * Get order history (diambil = picked up) for the logged-in customer.
     */
    public function history(Request $request)
    {
        $orders = Order::with('items')
            ->where('user_id', $request->user()->id)
            ->where('status', 'diambil')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders->map(fn (Order $order) => $this->formatOrder($order)),
        ]);
    }

    /**
     * Confirm order pickup (customer marks as "diambil").
     */
    public function confirmPickup(Request $request, Order $order)
    {
        // Ensure the order belongs to the authenticated user
        if ($request->user()->role !== User::ROLE_PENGGUNA || $order->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return DB::transaction(function () use ($order) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            if (! in_array($lockedOrder->status, ['selesai', 'diambil'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesanan belum selesai diproses.',
                ], 422);
            }

            // Retries after a slow network response must not perform pickup twice.
            if ($lockedOrder->status === 'selesai') {
                $lockedOrder->update(['status' => 'diambil']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Pesanan telah dikonfirmasi diambil.',
            ]);
        }, 3);
    }

    /**
     * Stream a payment proof only to the customer who owns the order.
     */
    public function paymentProof(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki akses ke bukti pembayaran ini.');
        }

        $path = (string) $order->payment_proof;
        if (
            $path === ''
            || ! str_starts_with($path, 'payment_proofs/')
            || ! Storage::disk('local')->exists($path)
        ) {
            abort(404);
        }

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Get QRIS image URL for the shop.
     */
    public function qrisImage()
    {
        $qrisPath = ShopSetting::qrisImagePath();

        if (! $qrisPath) {
            return response()->json([
                'success' => false,
                'message' => 'QRIS belum tersedia.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'image_url' => 'storage/'.$qrisPath,
            ],
        ]);
    }

    /**
     * Format order for JSON response.
     */
    private function formatOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'payment_method' => $order->payment_method,
            'total' => $order->total,
            'formatted_total' => $order->formatted_total,
            'cash_received' => $order->cash_received,
            'change_amount' => $order->change_amount,
            'payment_proof_url' => $order->payment_proof
                ? url('/api/orders/'.$order->id.'/payment-proof')
                : null,
            'status' => $order->status,
            'status_label' => $order->status_label,
            'paid_at' => $order->paid_at?->format('d/m/Y H:i'),
            'created_at' => $order->created_at->toIso8601String(),
            'items' => $order->items->map(fn ($i) => [
                'product_name' => $i->product_name,
                'variant' => $i->variant,
                'quantity' => $i->quantity,
                'price' => $i->price,
                'subtotal' => $i->subtotal,
            ])->toArray(),
        ];
    }
}
