<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;

class MaterialController extends Controller
{
    /**
     * Display a listing of raw materials.
     */
    public function index()
    {
        return view('pemilik.materials.index');
    }
}
