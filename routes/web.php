<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Pemilik\DashboardController as PemilikDashboardController;
use App\Http\Controllers\Pemilik\UserController;
use App\Http\Controllers\Pemilik\ProductController;
use App\Http\Controllers\Pemilik\CategoryController;
use App\Http\Controllers\Pemilik\MaterialController;

use App\Http\Controllers\Pemilik\ReportController;
use App\Http\Controllers\Pemilik\AnalyticsController;
use App\Http\Controllers\Pemilik\ExportController;
use App\Http\Controllers\Pemilik\CustomerController;
use App\Http\Controllers\Karyawan\DashboardController as KaryawanDashboardController;
use App\Http\Controllers\Karyawan\PosController;
use App\Http\Controllers\Karyawan\OrderController;

use App\Http\Middleware\CheckRole;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/', fn() => redirect()->route('login'));
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Pemilik Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', CheckRole::class . ':pemilik'])
    ->prefix('pemilik')
    ->name('pemilik.')
    ->group(function () {
        // Dashboard
        Route::get('/dashboard', [PemilikDashboardController::class, 'index'])->name('dashboard');

        // Kelola Data Pengguna
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

        // Kelola Data Produk & Harga
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::patch('/products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

        // Kategori Produk
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::patch('/categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('categories.toggle-status');

        // Kelola Data Bahan Baku
        Route::get('/materials', [MaterialController::class, 'index'])->name('materials.index');
        Route::post('/materials', [MaterialController::class, 'store'])->name('materials.store');
        Route::put('/materials/{ingredient}', [MaterialController::class, 'update'])->name('materials.update');
        Route::delete('/materials/{ingredient}', [MaterialController::class, 'destroy'])->name('materials.destroy');


        // Laporan
        Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('/reports/transactions', [ReportController::class, 'transactions'])->name('reports.transactions');
        Route::get('/reports/transactions/{order}/receipt', [ReportController::class, 'receipt'])->name('reports.transactions.receipt');
        Route::get('/reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');

        // Analisis Bisnis
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

        // Ekspor Data
        Route::get('/exports', [ExportController::class, 'index'])->name('exports.index');

        // Pelanggan
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    });

/*
|--------------------------------------------------------------------------
| Karyawan Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', CheckRole::class . ':karyawan'])
    ->prefix('karyawan')
    ->name('karyawan.')
    ->group(function () {
        // Dashboard
        Route::get('/dashboard', [KaryawanDashboardController::class, 'index'])->name('dashboard');

        // Point of Sales
        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::get('/pos/{product}/detail', [PosController::class, 'productDetail'])->name('pos.detail');
        Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');

        // Antrean Pesanan
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
        Route::get('/orders/{order}/receipt', [OrderController::class, 'receipt'])->name('orders.receipt');

        // Riwayat Transaksi
        Route::get('/riwayat-transaksi', [OrderController::class, 'history'])->name('orders.history');
        Route::get('/orders/{order}/detail', [OrderController::class, 'show'])->name('orders.show');
        Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');

        // Pelanggan
        Route::get('/pelanggan', [OrderController::class, 'customers'])->name('customers.index');


    });
