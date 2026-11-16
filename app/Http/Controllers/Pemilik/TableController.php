<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\TableManagementController;

class TableController extends TableManagementController
{
    public function index()
    {
        return view('pemilik.tables.index');
    }
}
