<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    /**
     * Show the karyawan dashboard.
     */
    public function index()
    {
        return view('karyawan.dashboard');
    }
}
