<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;

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
            'total_orders' => \App\Models\Order::where('cashier_id', $userId)
                ->whereDate('created_at', today())->count(),
            'pending_orders' => \App\Models\Order::where('cashier_id', $userId)
                ->whereDate('created_at', today())
                ->whereIn('status', ['menunggu_verifikasi', 'antrian_baru', 'sedang_dibuat'])->count(),
            'completed_orders' => \App\Models\Order::where('cashier_id', $userId)
                ->whereDate('created_at', today())
                ->whereIn('status', ['selesai', 'diambil'])->count(),
            'revenue' => \App\Models\Order::where('cashier_id', $userId)
                ->whereDate('created_at', today())
                ->whereIn('status', ['selesai', 'diambil'])->sum('total'),
        ];

        // 7-day revenue chart data for this employee
        $sevenDaysAgo = now()->subDays(6)->startOfDay();
        
        $chartData = \App\Models\Order::selectRaw('DATE(created_at) as date, SUM(total) as revenue')
            ->where('cashier_id', $userId)
            ->whereIn('status', ['selesai', 'diambil'])
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
