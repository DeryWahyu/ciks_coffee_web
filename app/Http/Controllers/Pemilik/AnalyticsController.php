<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Display analytics dashboard with sales trends, peak hours, KPIs, etc.
     */
    public function index(Request $request)
    {
        $period = $request->get('period', '30'); // 7, 30, 90 days
        $days = (int) $period;
        $startDate = Carbon::today()->subDays($days - 1);
        $endDate = Carbon::today();

        // Previous period for comparison
        $prevStart = $startDate->copy()->subDays($days);
        $prevEnd = $startDate->copy()->subDay();

        // ==================== KPI STATS ====================
        $currentRevenue = Order::whereIn('status', ['selesai', 'diambil'])
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->sum('total');

        $prevRevenue = Order::whereIn('status', ['selesai', 'diambil'])
            ->whereDate('created_at', '>=', $prevStart)
            ->whereDate('created_at', '<=', $prevEnd)
            ->sum('total');

        $currentOrders = Order::whereIn('status', ['selesai', 'diambil'])
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->count();

        $prevOrders = Order::whereIn('status', ['selesai', 'diambil'])
            ->whereDate('created_at', '>=', $prevStart)
            ->whereDate('created_at', '<=', $prevEnd)
            ->count();

        $currentAvg = $currentOrders > 0 ? $currentRevenue / $currentOrders : 0;
        $prevAvg = $prevOrders > 0 ? $prevRevenue / $prevOrders : 0;

        $currentCustomers = Order::whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->distinct('customer_name')->count('customer_name');

        $prevCustomers = Order::whereDate('created_at', '>=', $prevStart)
            ->whereDate('created_at', '<=', $prevEnd)
            ->distinct('customer_name')->count('customer_name');

        $kpi = [
            'revenue' => ['current' => $currentRevenue, 'prev' => $prevRevenue, 'change' => $prevRevenue > 0 ? (($currentRevenue - $prevRevenue) / $prevRevenue) * 100 : 0],
            'orders' => ['current' => $currentOrders, 'prev' => $prevOrders, 'change' => $prevOrders > 0 ? (($currentOrders - $prevOrders) / $prevOrders) * 100 : 0],
            'avg_order' => ['current' => $currentAvg, 'prev' => $prevAvg, 'change' => $prevAvg > 0 ? (($currentAvg - $prevAvg) / $prevAvg) * 100 : 0],
            'customers' => ['current' => $currentCustomers, 'prev' => $prevCustomers, 'change' => $prevCustomers > 0 ? (($currentCustomers - $prevCustomers) / $prevCustomers) * 100 : 0],
        ];

        // ==================== SALES TREND ====================
        $salesTrend = collect();
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $rev = Order::whereIn('status', ['selesai', 'diambil'])->whereDate('created_at', $date)->sum('total');
            $cnt = Order::whereIn('status', ['selesai', 'diambil'])->whereDate('created_at', $date)->count();
            $salesTrend->push([
                'date' => $days <= 14 ? $date->translatedFormat('d M') : $date->format('d/m'),
                'revenue' => (float)$rev,
                'orders' => $cnt,
            ]);
        }

        // ==================== TOP PRODUCTS ====================
        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('orders.status', ['selesai', 'diambil'])
            ->whereDate('orders.created_at', '>=', $startDate)
            ->whereDate('orders.created_at', '<=', $endDate)
            ->select(
                'order_items.product_name',
                'order_items.variant',
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.subtotal) as total_revenue')
            )
            ->groupBy('order_items.product_name', 'order_items.variant')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        // ==================== PEAK HOURS ====================
        $peakHours = Order::whereIn('status', ['selesai', 'diambil'])
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as revenue'))
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderBy('hour')
            ->get();

        // Fill missing hours 0-23
        $hourlyData = collect(range(0, 23))->map(function ($h) use ($peakHours) {
            $found = $peakHours->firstWhere('hour', $h);
            return [
                'hour' => sprintf('%02d:00', $h),
                'count' => $found ? $found->count : 0,
                'revenue' => $found ? (float)$found->revenue : 0,
            ];
        });

        // ==================== PAYMENT METHODS ====================
        $paymentMethods = Order::whereIn('status', ['selesai', 'diambil'])
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as total'))
            ->groupBy('payment_method')
            ->get();

        // ==================== DAILY AVERAGE ====================
        $dailyAvgRevenue = $days > 0 ? $currentRevenue / $days : 0;
        $dailyAvgOrders = $days > 0 ? $currentOrders / $days : 0;

        return view('pemilik.analytics.index', compact(
            'kpi', 'salesTrend', 'topProducts', 'hourlyData',
            'paymentMethods', 'dailyAvgRevenue', 'dailyAvgOrders',
            'period', 'startDate', 'endDate', 'days'
        ));
    }
}
