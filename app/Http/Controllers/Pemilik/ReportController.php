<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;

class ReportController extends Controller
{
    /**
     * Display sales report.
     */
    public function sales()
    {
        return view('pemilik.reports.sales');
    }

    /**
     * Display inventory report.
     */
    public function inventory()
    {
        return view('pemilik.reports.inventory');
    }
}
