<?php

namespace App\Http\Controllers\Api;

use App\Events\MobileOrderReceived;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShopSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
class OrderController extends Controller
{
    /**
     * Create a new order from mobile app (customer checkout).
     * Accepts multipart/form-data with optional payment_proof image.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:cash,qris',
            'cash_received'  => 'nullable|numeric|min:0',
            'items'          => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant'    => 'nullable|in:base,lite',
            'items.*.quantity'   => 'required|integer|min:1',
            'payment_proof'  => 'required_if:payment_method,qris|image|mimes:jpg,jpeg,png,webp|max:3072',
        ], [
            'items.required' => 'Pesanan tidak boleh kosong.',
            'payment_proof.required_if' => 'Bukti pembayaran wajib diunggah untuk pembayaran QRIS.',
            'payment_proof.max' => 'Ukuran bukti pembayaran maksimal 3MB.',
        ]);

        $order = DB::transaction(function () use ($validated, $request) {
            $total = 0;
            $orderItems = [];
            $ingredientRequirements = [];

            // Keep the selected product and recipe stable for the checkout.
            $products = Product::query()
                ->with('category')
                ->whereIn('id', collect($validated['items'])->pluck('product_id')->unique())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($validated['items'] as $item) {
                $product = $products->get($item['product_id']);

                if (!$product || !$product->is_active) {
                    throw ValidationException::withMessages([
                        'items' => 'Produk yang dipilih sudah tidak tersedia.',
                    ]);
                }

                $variantLabel = $this->resolveVariant($product, $item['variant'] ?? null);
                $unitPrice = $variantLabel === 'lite' ? $product->price_lite : $product->price;
                $subtotal = $unitPrice * $item['quantity'];
                $total += $subtotal;

                $productName = $product->name;
                if ($variantLabel) {
                    $productName .= ' (' . ucfirst($variantLabel) . ')';
                }

                $orderItems[] = [
                    'product_id'   => $product->id,
                    'product_name' => $productName,
                    'variant'      => $variantLabel,
                    'quantity'     => $item['quantity'],
                    'price'        => $unitPrice,
                    'subtotal'     => $subtotal,
                ];

                // Sum all uses of the same ingredient before checking stock.
                $ingredients = $product->ingredientsByVariant($variantLabel);
                foreach ($ingredients as $ingredient) {
                    $required = (float) $ingredient->pivot->quantity * $item['quantity'];
                    $ingredientRequirements[$ingredient->id] = ($ingredientRequirements[$ingredient->id] ?? 0) + $required;
                }
            }

            if ($validated['payment_method'] === 'cash') {
                $cashReceived = $validated['cash_received'] ?? $total;
                if ($cashReceived < $total) {
                    throw ValidationException::withMessages([
                        'cash_received' => 'Uang diterima kurang dari total belanja.'
                    ]);
                }
            } else {
                $cashReceived = null;
            }

            $changeAmount = $cashReceived ? max(0, $cashReceived - $total) : null;

            // Lock each ingredient once, in a stable order, then validate the
            // complete requirement before creating an order or reducing stock.
            $lockedIngredients = Ingredient::query()
                ->whereIn('id', array_keys($ingredientRequirements))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($ingredientRequirements as $ingredientId => $required) {
                $ingredient = $lockedIngredients->get($ingredientId);
                if (!$ingredient || (float) $ingredient->stok < $required) {
                    $ingredientName = $ingredient?->nama_bahan ?? 'yang dipilih';
                    throw ValidationException::withMessages([
                        'items' => "Stok bahan {$ingredientName} tidak cukup untuk pesanan ini.",
                    ]);
                }
            }

            // Handle payment proof upload
            $paymentProofPath = null;
            if ($request->hasFile('payment_proof')) {
                $paymentProofPath = $request->file('payment_proof')
                    ->store('payment_proofs', 'local');
            }

            // QRIS with proof → menunggu_verifikasi, otherwise → antrian_baru
            $status = ($validated['payment_method'] === 'qris' && $paymentProofPath)
                ? 'menunggu_verifikasi'
                : 'antrian_baru';

            // customer_name is taken from the authenticated user
            $order = Order::create([
                'order_number'   => Order::generateOrderNumber(),
                'customer_name'  => Auth::user()->name,
                'user_id'        => Auth::id(),
                'payment_method' => $validated['payment_method'],
                'total'          => $total,
                'cash_received'  => $cashReceived,
                'change_amount'  => $changeAmount,
                'payment_proof'  => $paymentProofPath,
                'status'         => $status,
                'paid_at'        => now(),
            ]);

            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            // Only deduct stock immediately for non-verification orders
            // For menunggu_verifikasi, stock will be deducted upon approval
            if ($status !== 'menunggu_verifikasi') {
                foreach ($ingredientRequirements as $ingredientId => $deduction) {
                    $lockedIngredients->get($ingredientId)->decrement('stok', $deduction);
                }
            }

            return $order;
        }, 3);

        $order->load('items');
        MobileOrderReceived::dispatch($order);

        return response()->json([
            'success' => true,
            'order' => $this->formatOrder($order),
        ], 201);
    }

    /**
     * Resolve and validate the variant accepted for a product.
     * Coffee always uses base or lite; other products must not have a variant.
     */
    private function resolveVariant(Product $product, ?string $variant): ?string
    {
        if ($product->category?->isCoffee()) {
            if (!in_array($variant, ['base', 'lite'], true)) {
                throw ValidationException::withMessages([
                    'items' => "Varian Base atau Lite wajib dipilih untuk produk {$product->name}.",
                ]);
            }

            if ($variant === 'lite' && $product->price_lite === null) {
                throw ValidationException::withMessages([
                    'items' => "Varian Lite tidak tersedia untuk produk {$product->name}.",
                ]);
            }

            return $variant;
        }

        if ($variant !== null) {
            throw ValidationException::withMessages([
                'items' => "Produk {$product->name} tidak memiliki varian.",
            ]);
        }

        return null;
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
            'data' => $orders->map(fn(Order $order) => $this->formatOrder($order)),
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
            'data' => $orders->map(fn(Order $order) => $this->formatOrder($order)),
        ]);
    }

    /**
     * Confirm order pickup (customer marks as "diambil").
     */
    public function confirmPickup(Request $request, Order $order)
    {
        // Ensure the order belongs to the authenticated user
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Only allow pickup confirmation for 'selesai' orders
        if ($order->status !== 'selesai') {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan belum selesai diproses.'
            ], 422);
        }

        $order->update(['status' => 'diambil']);

        return response()->json([
            'success' => true,
            'message' => 'Pesanan telah dikonfirmasi diambil.',
        ]);
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
            || !str_starts_with($path, 'payment_proofs/')
            || !Storage::disk('local')->exists($path)
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
        $qrisPath = ShopSetting::getValue('qris_image');

        if (!$qrisPath) {
            return response()->json([
                'success' => false,
                'message' => 'QRIS belum tersedia.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'image_url' => 'storage/' . $qrisPath,
            ],
        ]);
    }

    /**
     * Format order for JSON response.
     */
    private function formatOrder(Order $order): array
    {
        return [
            'id'              => $order->id,
            'order_number'    => $order->order_number,
            'customer_name'   => $order->customer_name,
            'payment_method'  => $order->payment_method,
            'total'           => $order->total,
            'formatted_total' => $order->formatted_total,
            'cash_received'   => $order->cash_received,
            'change_amount'   => $order->change_amount,
            'payment_proof_url' => $order->payment_proof
                ? url('/api/orders/' . $order->id . '/payment-proof')
                : null,
            'status'          => $order->status,
            'status_label'    => $order->status_label,
            'paid_at'         => $order->paid_at?->format('d/m/Y H:i'),
            'created_at'      => $order->created_at->toIso8601String(),
            'items'           => $order->items->map(fn($i) => [
                'product_name' => $i->product_name,
                'variant'      => $i->variant,
                'quantity'     => $i->quantity,
                'price'        => $i->price,
                'subtotal'     => $i->subtotal,
            ])->toArray(),
        ];
    }
}
