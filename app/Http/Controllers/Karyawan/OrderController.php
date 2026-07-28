<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    /**
     * Show the shared queue of active orders.
     */
    public function index(Request $request)
    {
        $status = $request->get('status');
        $allowedStatuses = ['menunggu_verifikasi', 'antrian_baru', 'sedang_dibuat', 'selesai'];
        $activeOrders = Order::query()->activeQueue();

        $query = (clone $activeOrders)
            ->with(['items', 'user', 'cashier'])
            ->latest();

        if ($status && in_array($status, $allowedStatuses, true)) {
            $query->where('status', $status);
        }

        $orders = $query->paginate(15)->withQueryString();
        $counts = [];

        foreach ($allowedStatuses as $queueStatus) {
            $counts[$queueStatus] = (clone $activeOrders)
                ->where('status', $queueStatus)
                ->count();
        }

        return view('karyawan.orders.index', compact('orders', 'counts', 'status'));
    }

    /**
     * Show only transactions handled by the authenticated employee.
     */
    public function history(Request $request)
    {
        $userId = $request->user()->id;
        $query = Order::with(['items', 'user', 'cashier'])
            ->where('cashier_id', $userId)
            ->latest();

        // Search by order number or customer name
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        // Filter by date range
        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Filter by payment method
        if ($payment = $request->get('payment_method')) {
            $query->where('payment_method', $payment);
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $orders = $query->paginate(20)->withQueryString();

        // Summary stats
        $statsQuery = Order::query()->where('cashier_id', $userId);
        if ($search) {
            $statsQuery->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }
        if ($dateFrom) {
            $statsQuery->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $statsQuery->whereDate('created_at', '<=', $dateTo);
        }
        if ($payment) {
            $statsQuery->where('payment_method', $payment);
        }
        if ($status) {
            $statsQuery->where('status', $status);
        }

        $stats = [
            'total_transactions' => (clone $statsQuery)->count(),
            'total_revenue' => (clone $statsQuery)->sum('total'),
            'avg_transaction' => (clone $statsQuery)->avg('total') ?? 0,
            'today_count' => Order::where('cashier_id', $userId)->whereDate('created_at', today())->count(),
        ];

        return view('karyawan.orders.history', compact('orders', 'stats'));
    }

    /**
     * Get order detail (AJAX).
     */
    public function show(Order $order)
    {
        if ($order->cashier_id !== null && $order->cashier_id !== request()->user()->id) {
            abort(403, 'Anda tidak memiliki akses ke pesanan ini.');
        }

        $order->load(['items', 'user', 'cashier']);

        return response()->json([
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'payment_method' => $order->payment_method,
            'total' => $order->total,
            'formatted_total' => $order->formatted_total,
            'cash_received' => $order->cash_received,
            'change_amount' => $order->change_amount,
            'status' => $order->status,
            'status_label' => $order->status_label,
            'status_color' => $order->status_color,
            'paid_at' => $order->paid_at?->format('d/m/Y H:i'),
            'created_at' => $order->created_at->format('d/m/Y H:i'),
            'cashier' => $order->cashier->name ?? '-',
            'items' => $order->items->map(fn ($i) => [
                'product_name' => $i->product_name,
                'variant' => $i->variant,
                'quantity' => $i->quantity,
                'price' => $i->price,
                'subtotal' => $i->subtotal,
            ]),
        ]);
    }

    /**
     * Stream a payment proof only to the employee responsible for the order.
     */
    public function paymentProof(Request $request, Order $order)
    {
        if ($order->cashier_id !== null && $order->cashier_id !== $request->user()->id) {
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
     * Update order status.
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:sedang_dibuat,selesai',
        ]);

        try {
            $updatedOrder = DB::transaction(function () use ($order, $validated, $request) {
                $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

                $allowedTransitions = [
                    'antrian_baru' => 'sedang_dibuat',
                    'sedang_dibuat' => 'selesai',
                ];

                if (($allowedTransitions[$lockedOrder->status] ?? null) !== $validated['status']) {
                    throw new \DomainException('Perubahan status pesanan tidak valid. Pesanan harus diproses sesuai urutan.');
                }

                if ($lockedOrder->cashier_id !== null && $lockedOrder->cashier_id !== $request->user()->id) {
                    throw new \DomainException('Pesanan ini sudah ditangani oleh karyawan lain.');
                }

                $lockedOrder->update([
                    'status' => $validated['status'],
                    'cashier_id' => $lockedOrder->cashier_id ?? $request->user()->id,
                ]);

                return $lockedOrder->fresh();
            }, 3);
        } catch (\DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Pesanan #{$updatedOrder->order_number} diperbarui ke \"{$updatedOrder->status_label}\".");
    }

    /**
     * Verify QRIS payment proof and move order to antrian_baru.
     * Also deducts ingredient stock at this point.
     */
    public function verifyPayment(Request $request, Order $order)
    {
        try {
            $verifiedOrder = DB::transaction(function () use ($order, $request) {
                $lockedOrder = Order::query()
                    ->with('items')
                    ->lockForUpdate()
                    ->findOrFail($order->id);

                if (
                    $lockedOrder->status !== 'menunggu_verifikasi'
                    || $lockedOrder->payment_method !== 'qris'
                    || empty($lockedOrder->payment_proof)
                ) {
                    throw new \DomainException('Pesanan ini tidak dapat diverifikasi.');
                }

                if ($lockedOrder->cashier_id !== null && $lockedOrder->cashier_id !== $request->user()->id) {
                    throw new \DomainException('Pesanan ini sudah ditangani oleh karyawan lain.');
                }

                $requirements = [];

                foreach ($lockedOrder->items as $item) {
                    $product = Product::query()->lockForUpdate()->find($item->product_id);

                    if (! $product) {
                        throw new \DomainException("Produk untuk item {$item->product_name} tidak ditemukan.");
                    }

                    foreach ($product->ingredientsByVariant($item->variant) as $ingredient) {
                        $required = (float) $ingredient->pivot->quantity * $item->quantity;
                        $requirements[$ingredient->id] = ($requirements[$ingredient->id] ?? 0) + $required;
                    }
                }

                $ingredients = Ingredient::query()
                    ->whereIn('id', array_keys($requirements))
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($requirements as $ingredientId => $required) {
                    $ingredient = $ingredients->get($ingredientId);

                    if (! $ingredient) {
                        throw new \DomainException('Salah satu bahan pesanan tidak ditemukan.');
                    }

                    if ((float) $ingredient->stok < $required) {
                        throw new \DomainException("Stok bahan {$ingredient->nama_bahan} tidak cukup untuk memverifikasi pesanan ini.");
                    }
                }

                foreach ($requirements as $ingredientId => $required) {
                    $ingredients->get($ingredientId)->decrement('stok', $required);
                }

                $lockedOrder->update([
                    'status' => 'antrian_baru',
                    'cashier_id' => $request->user()->id,
                ]);

                return $lockedOrder->fresh();
            }, 3);
        } catch (\DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Pembayaran #{$verifiedOrder->order_number} berhasil diverifikasi. Pesanan masuk antrean.");
    }

    /**
     * Get order detail for receipt (AJAX).
     */
    public function receipt(Order $order)
    {
        if ($order->cashier_id !== null && $order->cashier_id !== request()->user()->id) {
            abort(403, 'Anda tidak memiliki akses ke pesanan ini.');
        }

        $order->load(['items', 'user', 'cashier']);

        return response()->json([
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'payment_method' => $order->payment_method,
            'total' => $order->total,
            'formatted_total' => $order->formatted_total,
            'cash_received' => $order->cash_received,
            'change_amount' => $order->change_amount,
            'status_label' => $order->status_label,
            'paid_at' => $order->paid_at?->format('d/m/Y H:i'),
            'cashier' => $order->cashier->name ?? '-',
            'items' => $order->items->map(fn ($i) => [
                'product_name' => $i->product_name,
                'quantity' => $i->quantity,
                'price' => $i->price,
                'subtotal' => $i->subtotal,
            ]),
        ]);
    }

    /**
     * Show employee income page.
     */
    public function income(Request $request)
    {
        $userId = $request->user()->id;

        $query = Order::where('cashier_id', $userId)->whereIn('status', ['selesai', 'diambil']);

        // Filter by date range
        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Stats
        $stats = [
            'total_revenue' => (clone $query)->sum('total'),
            'total_transactions' => (clone $query)->count(),
            'avg_transaction' => (clone $query)->avg('total') ?? 0,
            'today_revenue' => (clone $query)->whereDate('created_at', today())->sum('total'),
        ];

        // Group by date for the table
        $incomeByDate = (clone $query)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as transactions, SUM(total) as revenue')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('karyawan.income.index', compact('stats', 'incomeByDate'));
    }
}
