<?php

namespace App\Support;

use Illuminate\Http\Request;

final class ReportDateRange
{
    public static function validate(Request $request, string $from, string $to): void
    {
        foreach ([$from, $to] as $field) {
            if ($request->input($field) === null || $request->input($field) === '') {
                $request->query->remove($field);
                $request->request->remove($field);
            }
        }
        $rules = [$from => ['nullable', 'date_format:Y-m-d'], $to => ['nullable', 'date_format:Y-m-d']];
        if ($request->filled($from) && $request->filled($to)) {
            $rules[$to][] = 'after_or_equal:'.$from;
        }
        $request->validate($rules);
    }
}
