<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Show frequent customers page for Pemilik.
     */
    public function index(Request $request)
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

        return view('pemilik.customers.index', compact('customers', 'stats', 'period'));
    }
}
