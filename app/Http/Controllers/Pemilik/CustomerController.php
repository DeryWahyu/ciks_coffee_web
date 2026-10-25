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
        $request->validate(['period' => 'nullable|in:all,week,month']);
        $period = $request->get('period', 'all'); // all, week, month

        $baseQuery = Order::visibleInTransactionHistory();
        if ($period === 'week') {
            $baseQuery->where('created_at', '>=', now()->subWeek());
        } elseif ($period === 'month') {
            $baseQuery->where('created_at', '>=', now()->subMonth());
        }
        if ($search = $request->get('search')) {
            $baseQuery->where('customer_name', 'like', "%{$search}%");
        }
        $customers = (clone $baseQuery)->select(
            'customer_name', DB::raw('COUNT(*) as total_orders'), DB::raw('SUM(total) as total_spent'),
            DB::raw('AVG(total) as avg_spent'), DB::raw('MAX(created_at) as last_order_at'), DB::raw('MIN(created_at) as first_order_at')
        )->groupBy('customer_name')->orderByDesc('total_orders')->paginate(20)->withQueryString();
        $topCustomer = (clone $baseQuery)->select('customer_name', DB::raw('COUNT(*) as cnt'))
            ->groupBy('customer_name')->orderByDesc('cnt')->first();
        $stats = [
            'unique_customers' => (clone $baseQuery)->distinct('customer_name')->count('customer_name'),
            'top_customer' => $topCustomer?->customer_name ?? '-', 'top_customer_orders' => $topCustomer?->cnt ?? 0,
            'returning_customers' => (clone $baseQuery)->select('customer_name')->groupBy('customer_name')->havingRaw('COUNT(*) > 1')->get()->count(),
        ];

        return view('pemilik.customers.index', compact('customers', 'stats', 'period'));
    }
}
