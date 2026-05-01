<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Show order queue page (today's orders).
     */
    public function index(Request $request)
    {
        $status = $request->get('status');

        $query = Order::with('items')->latest();

        if ($status && in_array($status, ['antrian_baru', 'sedang_dibuat', 'selesai'])) {
            $query->where('status', $status);
        }

        // Only show today's orders by default
        if (!$request->filled('all')) {
            $query->whereDate('created_at', today());
        }

        $orders = $query->paginate(15)->withQueryString();

        $counts = [
            'antrian_baru' => Order::whereDate('created_at', today())->where('status', 'antrian_baru')->count(),
            'sedang_dibuat' => Order::whereDate('created_at', today())->where('status', 'sedang_dibuat')->count(),
            'selesai' => Order::whereDate('created_at', today())->where('status', 'selesai')->count(),
        ];

        return view('karyawan.orders.index', compact('orders', 'counts', 'status'));
    }

    /**
     * Show transaction history (all recorded transactions).
     */
    public function history(Request $request)
    {
        $query = Order::with(['items', 'user'])->latest();

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
        $statsQuery = Order::query();
        if ($search) {
            $statsQuery->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }
        if ($dateFrom) $statsQuery->whereDate('created_at', '>=', $dateFrom);
        if ($dateTo) $statsQuery->whereDate('created_at', '<=', $dateTo);
        if ($payment) $statsQuery->where('payment_method', $payment);
        if ($status) $statsQuery->where('status', $status);

        $stats = [
            'total_transactions' => $statsQuery->count(),
            'total_revenue' => $statsQuery->sum('total'),
            'avg_transaction' => $statsQuery->avg('total') ?? 0,
            'today_count' => Order::whereDate('created_at', today())->count(),
        ];

        return view('karyawan.orders.history', compact('orders', 'stats'));
    }

    /**
     * Get order detail (AJAX).
     */
    public function show(Order $order)
    {
        $order->load(['items', 'user']);

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
            'cashier' => $order->user->name ?? '-',
            'items' => $order->items->map(fn($i) => [
                'product_name' => $i->product_name,
                'variant' => $i->variant,
                'quantity' => $i->quantity,
                'price' => $i->price,
                'subtotal' => $i->subtotal,
            ]),
        ]);
    }

    /**
     * Update order status.
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:antrian_baru,sedang_dibuat,selesai',
        ]);

        $order->update(['status' => $validated['status']]);

        return back()->with('success', "Pesanan #{$order->order_number} diperbarui ke \"{$order->status_label}\".");
    }

    /**
     * Delete an order.
     */
    public function destroy(Order $order)
    {
        $orderNumber = $order->order_number;
        $order->items()->delete();
        $order->delete();

        return back()->with('success', "Transaksi #{$orderNumber} berhasil dihapus.");
    }

    /**
     * Get order detail for receipt (AJAX).
     */
    public function receipt(Order $order)
    {
        $order->load('items', 'user');

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
            'cashier' => $order->user->name,
            'items' => $order->items->map(fn($i) => [
                'product_name' => $i->product_name,
                'quantity' => $i->quantity,
                'price' => $i->price,
                'subtotal' => $i->subtotal,
            ]),
        ]);
    }

    /**
     * Show frequent customers page.
     */
    public function customers(Request $request)
    {
        $period = $request->get('period', 'all'); // all, week, month

        $query = Order::select(
                'customer_name',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total) as total_spent'),
                DB::raw('AVG(total) as avg_spent'),
                DB::raw('MAX(created_at) as last_order_at'),
                DB::raw('MIN(created_at) as first_order_at')
            )
            ->groupBy('customer_name');

        if ($period === 'week') {
            $query->where('created_at', '>=', now()->subWeek());
        } elseif ($period === 'month') {
            $query->where('created_at', '>=', now()->subMonth());
        }

        // Search
        if ($search = $request->get('search')) {
            $query->where('customer_name', 'like', "%{$search}%");
        }

        $customers = $query->orderByDesc('total_orders')
            ->paginate(20)
            ->withQueryString();

        // Top stats
        $topCustomer = Order::select('customer_name', DB::raw('COUNT(*) as cnt'))
            ->groupBy('customer_name')
            ->orderByDesc('cnt')
            ->first();

        $stats = [
            'unique_customers' => Order::distinct('customer_name')->count('customer_name'),
            'top_customer' => $topCustomer?->customer_name ?? '-',
            'top_customer_orders' => $topCustomer?->cnt ?? 0,
            'returning_customers' => Order::select('customer_name')
                ->groupBy('customer_name')
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->count(),
        ];

        return view('karyawan.orders.customers', compact('customers', 'stats', 'period'));
    }
}
