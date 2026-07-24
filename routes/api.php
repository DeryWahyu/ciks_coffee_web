<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\TableLayoutController;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Public routes for fetching products and categories (can be accessed without auth if needed, but let's put it outside auth:sanctum for now or inside if we only want logged-in users to see them. Since the user said "setelah berhasil login", let's put it inside auth:sanctum to be secure).
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    // Products and Categories
    Route::get('/categories', [ProductController::class, 'categories']);
    Route::get('/products', [ProductController::class, 'index']);

    // Table availability for the authenticated mobile customer (read-only).
    Route::get('/table-layout', [TableLayoutController::class, 'index'])
        ->middleware('throttle:60,1');

    // Mobile Orders (Customer)
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/active', [OrderController::class, 'active']);
    Route::get('/orders/history', [OrderController::class, 'history']);
    Route::post('/orders/{order}/pickup', [OrderController::class, 'confirmPickup']);

    // Shop Settings
    Route::get('/shop/qris', [OrderController::class, 'qrisImage']);
});

// Serve storage files with CORS for Flutter Web local development
Route::get('/image/{path}', function ($path) {
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
