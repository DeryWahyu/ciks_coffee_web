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
            $baseQuery->where('orders.created_at', '>=', now()->subWeek());
        } elseif ($period === 'month') {
            $baseQuery->where('orders.created_at', '>=', now()->subMonth());
        }
        if ($search = $request->get('search')) {
            $baseQuery->where('customer_name', 'like', "%{$search}%");
        }
        $mobileIdentity = "CASE WHEN customer_account.role = 'pengguna' THEN orders.user_id ELSE 0 END";
        $posIdentity = "CASE WHEN customer_account.role = 'pengguna' THEN '' ELSE orders.customer_name END";
        $groups = (clone $baseQuery)->join('users as customer_account', 'customer_account.id', '=', 'orders.user_id')
            ->selectRaw("{$mobileIdentity} as mobile_customer_id, MIN(orders.customer_name) as customer_name,
                COUNT(*) as total_orders, SUM(orders.total) as total_spent, AVG(orders.total) as avg_spent,
                MAX(orders.created_at) as last_order_at, MIN(orders.created_at) as first_order_at")
            ->groupByRaw("{$mobileIdentity}, {$posIdentity}");
        $customers = (clone $groups)->orderByDesc('total_orders')->paginate(20)->withQueryString();
        $topCustomer = (clone $groups)->orderByDesc('total_orders')->first();
        $counts = DB::query()->fromSub((clone $groups)->toBase(), 'customer_groups')
            ->selectRaw('COUNT(*) as unique_customers, COALESCE(SUM(CASE WHEN total_orders > 1 THEN 1 ELSE 0 END), 0) as returning_customers')->first();
        $stats = [
            'unique_customers' => (int) $counts->unique_customers,
            'top_customer' => $topCustomer?->customer_name ?? '-', 'top_customer_orders' => $topCustomer?->total_orders ?? 0,
            'returning_customers' => (int) $counts->returning_customers,
        ];

        return view('pemilik.customers.index', compact('customers', 'stats', 'period'));
    }
}
