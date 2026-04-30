<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;

class TableMonitorController extends Controller
{
    /**
     * Show the table monitoring interface.
     */
    public function index()
    {
        return view('karyawan.tables.index');
    }
}
