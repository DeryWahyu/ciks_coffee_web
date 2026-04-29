<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Pemilik\DashboardController as PemilikDashboardController;
use App\Http\Controllers\Pemilik\UserController;
use App\Http\Controllers\Pemilik\ProductController;
use App\Http\Controllers\Pemilik\MaterialController;
use App\Http\Controllers\Pemilik\TableController;
use App\Http\Controllers\Pemilik\ReportController;
use App\Http\Controllers\Pemilik\AnalyticsController;
use App\Http\Controllers\Pemilik\ExportController;
use App\Http\Controllers\Karyawan\DashboardController as KaryawanDashboardController;
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

        // Kelola Data Bahan Baku
        Route::get('/materials', [MaterialController::class, 'index'])->name('materials.index');

        // Kelola Data Meja
        Route::get('/tables', [TableController::class, 'index'])->name('tables.index');

        // Laporan
        Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('/reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');

        // Analisis Bisnis
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

        // Ekspor Data
        Route::get('/exports', [ExportController::class, 'index'])->name('exports.index');
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
        Route::get('/dashboard', [KaryawanDashboardController::class, 'index'])->name('dashboard');
    });
