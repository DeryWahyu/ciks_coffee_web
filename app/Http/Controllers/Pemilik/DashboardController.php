<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;
use App\Models\User;

class DashboardController extends Controller
{
    /**
     * Show the pemilik dashboard.
     */
    public function index()
    {
        $totalKaryawan = User::where('role', 'karyawan')->where('is_active', true)->count();

        return view('pemilik.dashboard', compact('totalKaryawan'));
    }
}
