<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
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

        $todayOrders = Order::whereDate('created_at', $today)->count();
        $todayRevenue = Order::whereIn('status', ['selesai', 'diambil'])->whereDate('created_at', $today)->sum('total');
        $pendingOrders = Order::whereIn('status', ['menunggu_verifikasi', 'antrian_baru', 'sedang_dibuat'])->count();

        // This month stats
        $monthStart = $today->copy()->startOfMonth();
        $monthRevenue = Order::whereIn('status', ['selesai', 'diambil'])
            ->whereDate('created_at', '>=', $monthStart)
            ->sum('total');
        $monthOrders = Order::whereIn('status', ['selesai', 'diambil'])
            ->whereDate('created_at', '>=', $monthStart)
            ->count();

        // Last 7 days chart data
        $last7Days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $revenue = Order::whereIn('status', ['selesai', 'diambil'])
                ->whereDate('created_at', $date)
                ->sum('total');
            $orders = Order::whereIn('status', ['selesai', 'diambil'])
                ->whereDate('created_at', $date)
                ->count();
            $last7Days->push([
                'date' => $date->translatedFormat('d M'),
                'day' => $date->translatedFormat('D'),
                'revenue' => (float) $revenue,
                'orders' => $orders,
            ]);
        }

        // Top 5 products this month
        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('orders.status', ['selesai', 'diambil'])
            ->whereDate('orders.created_at', '>=', $monthStart)
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
        $paymentStats = Order::whereIn('status', ['selesai', 'diambil'])
            ->whereDate('created_at', '>=', $monthStart)
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
