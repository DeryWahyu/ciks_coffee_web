<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\TableManagementController;

class TableController extends TableManagementController
{
    public function index()
    {
        return view('karyawan.tables.index');
    }
}
