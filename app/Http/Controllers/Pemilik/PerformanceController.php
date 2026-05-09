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
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // Revenue per employee (only karyawan, exclude pengguna/pemilik)
        $employeeStats = Order::whereIn('orders.status', ['selesai', 'diambil'])
            ->whereDate('orders.created_at', '>=', $startDate)
            ->whereDate('orders.created_at', '<=', $endDate)
            ->join('users', 'orders.cashier_id', '=', 'users.id')
            ->where('users.role', 'karyawan')
            ->select(
                'orders.cashier_id as user_id',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(orders.total) as total_revenue'),
                DB::raw('AVG(orders.total) as avg_order_value')
            )
            ->groupBy('orders.cashier_id')
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
            'leaderboard', 'grandTotal', 'startDate', 'endDate'
        ));
    }

    /**
     * Display detail of what a specific employee has sold.
     */
    public function employeeDetail(Request $request, User $user)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // Products sold by this employee
        $productsSold = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.cashier_id', $user->id)
            ->whereIn('orders.status', ['selesai', 'diambil'])
            ->whereDate('orders.created_at', '>=', $startDate)
            ->whereDate('orders.created_at', '<=', $endDate)
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
        $summary = Order::where('cashier_id', $user->id)
            ->whereIn('status', ['selesai', 'diambil'])
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->selectRaw('COUNT(*) as total_orders, SUM(total) as total_revenue, AVG(total) as avg_order')
            ->first();

        // Recent orders
        $recentOrders = Order::with('items')
            ->where('cashier_id', $user->id)
            ->whereIn('status', ['selesai', 'diambil'])
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('pemilik.reports.employee-detail', compact(
            'user', 'productsSold', 'summary', 'recentOrders', 'startDate', 'endDate'
        ));
    }

    /**
     * Display product performance report (Pendapatan Per Produk / Best Seller).
     */
    public function products(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // Product performance: quantity sold + revenue
        $productStats = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereIn('orders.status', ['selesai', 'diambil'])
            ->whereDate('orders.created_at', '>=', $startDate)
            ->whereDate('orders.created_at', '<=', $endDate)
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
            'productStats', 'totalProductsSold', 'totalRevenue', 'topProduct', 'top5', 'startDate', 'endDate'
        ));
    }
}
