<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Display sales report.
     */
    public function sales(Request $request)
    {
        // Default to current month
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $query = Order::whereIn('status', ['selesai', 'diambil'])
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);

        // Summary Stats
        $totalRevenue = (clone $query)->sum('total');
        $totalOrders = (clone $query)->count();
        $totalCustomers = (clone $query)->distinct('customer_name')->count('customer_name');
        
        // Sales per day for the chart/table
        $salesByDate = (clone $query)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total) as revenue'), DB::raw('COUNT(*) as orders_count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top Selling Products in this period
        // We join order_items to orders to filter by date and status
        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('orders.status', ['selesai', 'diambil'])
            ->whereDate('orders.created_at', '>=', $startDate)
            ->whereDate('orders.created_at', '<=', $endDate)
            ->select('order_items.product_name', 'order_items.variant', DB::raw('SUM(order_items.quantity) as total_quantity'), DB::raw('SUM(order_items.subtotal) as total_revenue'))
            ->groupBy('order_items.product_name', 'order_items.variant')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        return view('pemilik.reports.sales', compact(
            'startDate', 'endDate', 'totalRevenue', 'totalOrders', 'totalCustomers', 'salesByDate', 'topProducts'
        ));
    }

    /**
     * Display inventory report.
     */
    public function inventory()
    {
        $ingredients = \App\Models\Ingredient::orderBy('stok', 'asc')->get();
        
        $totalIngredients = $ingredients->count();
        // Assuming stock < 20 is "Low"
        $lowStockCount = $ingredients->where('stok', '<=', 20)->count();
        $safeStockCount = $totalIngredients - $lowStockCount;

        // Data for chart (Top 10 lowest stock items)
        $chartData = $ingredients->take(10)->map(function ($item) {
            return [
                'name' => $item->nama_bahan,
                'stok' => (float) $item->stok,
                'satuan' => $item->satuan
            ];
        })->values();

        return view('pemilik.reports.inventory', compact(
            'ingredients', 'totalIngredients', 'lowStockCount', 'safeStockCount', 'chartData'
        ));
    }

    /**
     * Display transaction history.
     */
    public function transactions(Request $request)
    {
        $query = Order::with(['items', 'user', 'cashier'])->latest();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }
        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }
        if ($payment = $request->get('payment_method')) {
            $query->where('payment_method', $payment);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $orders = $query->paginate(20)->withQueryString();

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

        return view('pemilik.reports.transactions', compact('orders', 'stats'));
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
            'cashier' => $order->user->name ?? '-',
            'items' => $order->items->map(fn($i) => [
                'product_name' => $i->product_name,
                'quantity' => $i->quantity,
                'price' => $i->price,
                'subtotal' => $i->subtotal,
            ]),
        ]);
    }
}
