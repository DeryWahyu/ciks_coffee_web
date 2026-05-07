<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerformanceController extends Controller
{
    /**
     * Display employee performance report (Pendapatan Per Karyawan).
     */
    public function employees(Request $request)
    {
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $monthEnd = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        // Revenue per employee
        $employeeStats = Order::where('status', 'selesai')
            ->whereDate('created_at', '>=', $monthStart)
            ->whereDate('created_at', '<=', $monthEnd)
            ->select(
                'user_id',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total) as total_revenue'),
                DB::raw('AVG(total) as avg_order_value')
            )
            ->groupBy('user_id')
            ->orderByDesc('total_revenue')
            ->get();

        // Eager load user data
        $userIds = $employeeStats->pluck('user_id');
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        // Enrich with user names
        $leaderboard = $employeeStats->map(function ($stat, $index) use ($users) {
            $user = $users->get($stat->user_id);
            return (object) [
                'rank' => $index + 1,
                'user_id' => $stat->user_id,
                'name' => $user?->name ?? 'Unknown',
                'total_orders' => $stat->total_orders,
                'total_revenue' => $stat->total_revenue,
                'avg_order_value' => $stat->avg_order_value,
            ];
        });

        // Grand total for percentage calculation
        $grandTotal = $employeeStats->sum('total_revenue');

        return view('pemilik.reports.employee-performance', compact(
            'leaderboard', 'grandTotal', 'month', 'monthStart'
        ));
    }

    /**
     * Display detail of what a specific employee has sold.
     */
    public function employeeDetail(Request $request, User $user)
    {
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $monthEnd = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        // Products sold by this employee
        $productsSold = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.user_id', $user->id)
            ->where('orders.status', 'selesai')
            ->whereDate('orders.created_at', '>=', $monthStart)
            ->whereDate('orders.created_at', '<=', $monthEnd)
            ->select(
                'order_items.product_name',
                'order_items.variant',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.subtotal) as total_revenue'),
                DB::raw('COUNT(DISTINCT order_items.order_id) as total_orders')
            )
            ->groupBy('order_items.product_name', 'order_items.variant')
            ->orderByDesc('total_quantity')
            ->get();

        // Employee summary stats
        $summary = Order::where('user_id', $user->id)
            ->where('status', 'selesai')
            ->whereDate('created_at', '>=', $monthStart)
            ->whereDate('created_at', '<=', $monthEnd)
            ->selectRaw('COUNT(*) as total_orders, SUM(total) as total_revenue, AVG(total) as avg_order')
            ->first();

        // Recent orders
        $recentOrders = Order::with('items')
            ->where('user_id', $user->id)
            ->where('status', 'selesai')
            ->whereDate('created_at', '>=', $monthStart)
            ->whereDate('created_at', '<=', $monthEnd)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('pemilik.reports.employee-detail', compact(
            'user', 'productsSold', 'summary', 'recentOrders', 'month', 'monthStart'
        ));
    }

    /**
     * Display product performance report (Pendapatan Per Produk / Best Seller).
     */
    public function products(Request $request)
    {
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $monthEnd = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        // Product performance: quantity sold + revenue
        $productStats = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.status', 'selesai')
            ->whereDate('orders.created_at', '>=', $monthStart)
            ->whereDate('orders.created_at', '<=', $monthEnd)
            ->select(
                'order_items.product_id',
                'order_items.product_name',
                'order_items.variant',
                'products.image',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.subtotal) as total_revenue'),
                DB::raw('COUNT(DISTINCT order_items.order_id) as total_orders')
            )
            ->groupBy('order_items.product_id', 'order_items.product_name', 'order_items.variant', 'products.image')
            ->orderByDesc('total_quantity')
            ->get();

        // Summary
        $totalProductsSold = $productStats->sum('total_quantity');
        $totalRevenue = $productStats->sum('total_revenue');
        $topProduct = $productStats->first();

        // Top 5 for chart
        $top5 = $productStats->take(5);

        return view('pemilik.reports.product-performance', compact(
            'productStats', 'totalProductsSold', 'totalRevenue', 'topProduct', 'top5', 'month', 'monthStart'
        ));
    }
}
