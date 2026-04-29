<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;

class AnalyticsController extends Controller
{
    /**
     * Display analytics dashboard.
     */
    public function index()
    {
        return view('pemilik.analytics.index');
    }
}
