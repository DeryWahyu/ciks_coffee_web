<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;

class ExportController extends Controller
{
    /**
     * Display export page.
     */
    public function index()
    {
        return view('pemilik.exports.index');
    }
}
