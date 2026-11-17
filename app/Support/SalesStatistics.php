<?php

namespace App\Support;

use App\Models\Order;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class SalesStatistics
{
    private static function period(CarbonInterface $from, ?CarbonInterface $to): Builder
    {
        $query = Order::visibleInTransactionHistory()->where('orders.created_at', '>=', $from->copy()->startOfDay());
        if ($to) {
            $query->where('orders.created_at', '<', $to->copy()->addDay()->startOfDay());
        }

        return $query;
    }

    public static function totals(CarbonInterface $from, ?CarbonInterface $to = null): object
    {
        return self::period($from, $to)->selectRaw('COALESCE(SUM(total), 0) as revenue, COUNT(*) as orders_count, COUNT(DISTINCT customer_name) as customers')->first();
    }

    public static function daily(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return self::period($from, $to)->selectRaw('DATE(created_at) as date, SUM(total) as revenue, COUNT(*) as orders_count')
            ->groupByRaw('DATE(created_at)')->orderBy('date')->get()->keyBy('date');
    }
}
