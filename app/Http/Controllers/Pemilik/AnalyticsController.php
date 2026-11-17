<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\SalesStatistics;
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
        $request->validate(['period' => 'nullable|in:7,30,90']);
        $period = $request->input('period') ?: '30'; // 7, 30, 90 days
        $days = (int) $period;
        $startDate = Carbon::today()->subDays($days - 1);
        $endDate = Carbon::today();

        // Previous period for comparison
        $prevStart = $startDate->copy()->subDays($days);
        $prevEnd = $startDate->copy()->subDay();

        // ==================== KPI STATS ====================
        $current = SalesStatistics::totals($startDate, $endDate);
        $previous = SalesStatistics::totals($prevStart, $prevEnd);
        $currentRevenue = $current->revenue;
        $prevRevenue = $previous->revenue;
        $currentOrders = (int) $current->orders_count;
        $prevOrders = (int) $previous->orders_count;
        $currentCustomers = (int) $current->customers;
        $prevCustomers = (int) $previous->customers;
        $currentAvg = $currentOrders > 0 ? $currentRevenue / $currentOrders : 0;
        $prevAvg = $prevOrders > 0 ? $prevRevenue / $prevOrders : 0;

        $kpi = [
            'revenue' => ['current' => $currentRevenue, 'prev' => $prevRevenue, 'change' => $prevRevenue > 0 ? (($currentRevenue - $prevRevenue) / $prevRevenue) * 100 : 0],
            'orders' => ['current' => $currentOrders, 'prev' => $prevOrders, 'change' => $prevOrders > 0 ? (($currentOrders - $prevOrders) / $prevOrders) * 100 : 0],
            'avg_order' => ['current' => $currentAvg, 'prev' => $prevAvg, 'change' => $prevAvg > 0 ? (($currentAvg - $prevAvg) / $prevAvg) * 100 : 0],
            'customers' => ['current' => $currentCustomers, 'prev' => $prevCustomers, 'change' => $prevCustomers > 0 ? (($currentCustomers - $prevCustomers) / $prevCustomers) * 100 : 0],
        ];

        // ==================== SALES TREND ====================
        $salesTrend = collect();
        $dailyStats = SalesStatistics::daily($startDate, $endDate);
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $stats = $dailyStats->get($date->toDateString());
            $rev = $stats?->revenue ?? 0;
            $cnt = (int) ($stats?->orders_count ?? 0);
            $salesTrend->push([
                'date' => $days <= 14 ? $date->translatedFormat('d M') : $date->format('d/m'),
                'revenue' => (float) $rev,
                'orders' => $cnt,
            ]);
        }

        // ==================== TOP PRODUCTS ====================
        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')

            ->whereIn('orders.id', Order::visibleInTransactionHistory()->select('orders.id'))
            ->where('orders.created_at', '>=', $startDate)
            ->where('orders.created_at', '<', $endDate->copy()->addDay()->startOfDay())
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
        $peakHours = Order::visibleInTransactionHistory()
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<', $endDate->copy()->addDay()->startOfDay())
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
                'revenue' => $found ? (float) $found->revenue : 0,
            ];
        });

        // ==================== PAYMENT METHODS ====================
        $paymentMethods = Order::visibleInTransactionHistory()
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<', $endDate->copy()->addDay()->startOfDay())
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
