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
use App\Http\Controllers\Pemilik\SettingController;
use App\Http\Controllers\Pemilik\TableController as PemilikTableController;
use App\Http\Controllers\Karyawan\DashboardController as KaryawanDashboardController;
use App\Http\Controllers\Karyawan\PosController;
use App\Http\Controllers\Karyawan\OrderController;
use App\Http\Controllers\Karyawan\TableController as KaryawanTableController;
use App\Http\Controllers\Pemilik\PerformanceController;

use App\Http\Middleware\CheckRole;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;

// Serve storage files with CORS for Flutter Web local development
Route::get('/storage/{path}', function ($path) {
    $filePath = storage_path('app/public/' . $path);
    $realPath = realpath($filePath);
    $basePath = realpath(storage_path('app/public'));

    if (!$realPath || !$basePath || !str_starts_with($realPath, $basePath . DIRECTORY_SEPARATOR)) {
        abort(404);
    }

    $file = file_get_contents($realPath);
    $type = mime_content_type($realPath);

    return Response::make($file, 200, [
        'Content-Type' => $type,
        'Access-Control-Allow-Origin' => config('app.url'),
        'Access-Control-Allow-Methods' => 'GET, OPTIONS',
    ]);
})->where('path', '.*');

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/', fn() => redirect()->route('login'));
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
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
        Route::patch('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

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

        // Performa
        Route::get('/performance/employees', [PerformanceController::class, 'employees'])->name('performance.employees');
        Route::get('/performance/employees/{user}', [PerformanceController::class, 'employeeDetail'])->name('performance.employee-detail');
        Route::get('/performance/products', [PerformanceController::class, 'products'])->name('performance.products');

        // Analisis Bisnis
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

        // Ekspor Data
        Route::get('/exports', [ExportController::class, 'index'])->name('exports.index');
        Route::get('/exports/csv', [ExportController::class, 'exportCsv'])->name('exports.csv');
        Route::get('/exports/excel', [ExportController::class, 'exportExcel'])->name('exports.excel');
        Route::get('/exports/pdf', [ExportController::class, 'exportPdf'])->name('exports.pdf');

        // Pelanggan
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');

        // Manajemen meja (sumber data untuk antarmuka Tahap 4)
        Route::get('/meja', [PemilikTableController::class, 'index'])->name('tables.index');
        Route::get('/meja/data', [PemilikTableController::class, 'layout'])->name('tables.data');
        Route::get('/meja/riwayat', [PemilikTableController::class, 'history'])->name('tables.history');
        Route::put('/meja/layout/{floorLayout}', [PemilikTableController::class, 'updateLayout'])->name('tables.layout.update');
        Route::post('/meja', [PemilikTableController::class, 'store'])->name('tables.store');
        Route::put('/meja/{coffeeTable}', [PemilikTableController::class, 'update'])->name('tables.update');
        Route::delete('/meja/{coffeeTable}', [PemilikTableController::class, 'destroy'])->name('tables.destroy');
        Route::patch('/meja/{coffeeTable}/status', [PemilikTableController::class, 'updateStatus'])->name('tables.status.update');
        Route::patch('/meja/{coffeeTable}/toggle-active', [PemilikTableController::class, 'updateActiveState'])->name('tables.toggle-active');

        // Pengaturan
        Route::get('/settings/qris', [SettingController::class, 'qris'])->name('settings.qris');
        Route::post('/settings/qris', [SettingController::class, 'updateQris'])->name('settings.qris.update');
        Route::delete('/settings/qris', [SettingController::class, 'deleteQris'])->name('settings.qris.delete');
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
        Route::patch('/orders/{order}/verify', [OrderController::class, 'verifyPayment'])->name('orders.verify');
        Route::get('/orders/{order}/receipt', [OrderController::class, 'receipt'])->name('orders.receipt');

        // Riwayat Transaksi
        Route::get('/riwayat-transaksi', [OrderController::class, 'history'])->name('orders.history');
        Route::get('/orders/{order}/detail', [OrderController::class, 'show'])->name('orders.show');
        Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');

        // Pendapatan Karyawan
        Route::get('/pendapatan', [OrderController::class, 'income'])->name('income.index');

        // Ketersediaan meja (sumber data untuk antarmuka Tahap 3)
        Route::get('/meja', [KaryawanTableController::class, 'index'])->name('tables.index');
        Route::get('/meja/data', [KaryawanTableController::class, 'layout'])->name('tables.data');
        Route::patch('/meja/{coffeeTable}/status', [KaryawanTableController::class, 'updateStatus'])->name('tables.status.update');

    });
