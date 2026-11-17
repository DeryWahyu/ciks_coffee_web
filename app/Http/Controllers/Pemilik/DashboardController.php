<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\SalesStatistics;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Show the pemilik dashboard with real-time stats and charts.
     */
    public function index()
    {
        $today = Carbon::today();

        // Summary Stats
        $totalKaryawan = User::where('role', 'karyawan')->where('is_active', true)->count();
        $totalProducts = Product::where('is_active', true)->count();

        $todayOrders = Order::where('created_at', '>=', $today)->where('created_at', '<', $today->copy()->addDay())->count();
        $todayRevenue = SalesStatistics::totals($today, $today)->revenue;
        $pendingOrders = Order::activeQueue()->count();

        // This month stats
        $monthStart = $today->copy()->startOfMonth();
        $monthStats = SalesStatistics::totals($monthStart);
        $monthRevenue = $monthStats->revenue;
        $monthOrders = $monthStats->orders_count;

        // Last 7 days chart data
        $last7Days = collect();
        $dailyStats = SalesStatistics::daily($today->copy()->subDays(6), $today);
        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $stats = $dailyStats->get($date->toDateString());
            $last7Days->push([
                'date' => $date->translatedFormat('d M'),
                'day' => $date->translatedFormat('D'),
                'revenue' => (float) ($stats?->revenue ?? 0),
                'orders' => (int) ($stats?->orders_count ?? 0),
            ]);
        }

        // Top 5 products this month
        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')

            ->whereIn('orders.id', Order::visibleInTransactionHistory()->select('orders.id'))
            ->where('orders.created_at', '>=', $monthStart)
            ->select('order_items.product_name', DB::raw('SUM(order_items.quantity) as total_qty'))
            ->groupBy('order_items.product_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // Recent 5 orders
        $recentOrders = Order::with(['user', 'cashier'])
            ->latest()
            ->limit(5)
            ->get();

        // Payment method distribution this month
        $paymentStats = Order::visibleInTransactionHistory()
            ->where('created_at', '>=', $monthStart)
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as total'))
            ->groupBy('payment_method')
            ->get();

        return view('pemilik.dashboard', compact(
            'totalKaryawan', 'totalProducts', 'todayOrders', 'todayRevenue',
            'pendingOrders', 'monthRevenue', 'monthOrders', 'last7Days',
            'topProducts', 'recentOrders', 'paymentStats'
        ));
    }
}
