<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;

class TableController extends Controller
{
    /**
     * Display a listing of tables.
     */
    public function index()
    {
        return view('pemilik.tables.index');
    }
}
