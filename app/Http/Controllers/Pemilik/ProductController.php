<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index()
    {
        return view('pemilik.products.index');
    }
}
