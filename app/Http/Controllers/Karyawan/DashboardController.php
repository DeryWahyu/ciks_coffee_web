<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Show the karyawan dashboard.
     */
    public function index()
    {
        $userId = Auth::id();

        $todayStats = [
            'total_orders' => Order::where('cashier_id', $userId)
                ->whereDate('created_at', today())->count(),
            'pending_orders' => Order::where('cashier_id', $userId)
                ->whereDate('created_at', today())
                ->activeQueue()->count(),
            'completed_orders' => Order::where('cashier_id', $userId)
                ->whereDate('created_at', today())
                ->visibleInTransactionHistory()->whereIn('status', ['selesai', 'diambil'])->count(),
            'revenue' => Order::where('cashier_id', $userId)
                ->whereDate('created_at', today())
                ->visibleInTransactionHistory()->sum('total'),
        ];

        // 7-day revenue chart data for this employee
        $sevenDaysAgo = now()->subDays(6)->startOfDay();

        $chartData = Order::selectRaw('DATE(created_at) as date, SUM(total) as revenue')
            ->where('cashier_id', $userId)
            ->visibleInTransactionHistory()
            ->where('created_at', '>=', $sevenDaysAgo)
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->pluck('revenue', 'date')
            ->toArray();

        // Fill in missing days with 0
        $labels = [];
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->translatedFormat('d M');
            $data[] = $chartData[$date] ?? 0;
        }

        $chart = [
            'labels' => $labels,
            'data' => $data,
        ];

        return view('karyawan.dashboard', compact('todayStats', 'chart'));
    }
}
