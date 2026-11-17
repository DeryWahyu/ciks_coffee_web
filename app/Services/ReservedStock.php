<?php

namespace App\Services;

use App\Support\Decimal;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class ReservedStock
{
    public static function active(): Builder
    {
        return DB::table('checkout_reservations')->whereNull('released_at')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    /** Ingredient rows are locked first; use a current read on InnoDB, not an older transaction snapshot. */
    public static function units(array $ids, ?string $except = null): array
    {
        $query = self::active()->whereIn('ingredient_id', $ids);
        if ($except !== null) {
            $query->where('mobile_checkout_id', '!=', $except);
        }
        $totals = [];
        foreach ($query->orderBy('ingredient_id')->orderBy('id')->lockForUpdate()->get() as $row) {
            $totals[$row->ingredient_id] = ($totals[$row->ingredient_id] ?? 0) + Decimal::units($row->quantity);
        }

        return $totals;
    }

    public static function release(string $checkoutId): void
    {
        DB::table('checkout_reservations')->where('mobile_checkout_id', $checkoutId)->whereNull('released_at')->update(['released_at' => now()]);
    }
}
