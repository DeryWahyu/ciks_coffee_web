<?php

namespace App\Support;

/** Exact arithmetic for the database's two-decimal money and stock columns. */
final class Decimal
{
    public const MAX_UNITS = 999999999999;

    public static function units(int|float|string $value): int
    {
        return (int) round((float) $value * 100, 0, PHP_ROUND_HALF_UP);
    }

    public static function value(int $units): string
    {
        $sign = $units < 0 ? '-' : '';
        $units = abs($units);

        return $sign.intdiv($units, 100).'.'.str_pad((string) ($units % 100), 2, '0', STR_PAD_LEFT);
    }
}
