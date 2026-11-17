<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\MobileCheckoutController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\TableLayoutController;
use App\Http\Controllers\PublicAssetController;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\PrivateResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1,mobile-register');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1,mobile-login');

// Public routes for fetching products and categories (can be accessed without auth if needed, but let's put it outside auth:sanctum for now or inside if we only want logged-in users to see them. Since the user said "setelah berhasil login", let's put it inside auth:sanctum to be secure).
Route::middleware(['auth:sanctum', EnsureActiveUser::class])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/device-tokens', [DeviceTokenController::class, 'store'])
        ->middleware('throttle:30,1,mobile-devices');
    Route::delete('/device-tokens', [DeviceTokenController::class, 'destroy'])
        ->middleware('throttle:30,1,mobile-devices');

    // Products and Categories
    Route::get('/categories', [ProductController::class, 'categories']);
    Route::get('/products', [ProductController::class, 'index']);

    // Table availability for the authenticated mobile customer (read-only).
    Route::get('/table-layout', [TableLayoutController::class, 'index'])
        ->middleware('throttle:60,1,mobile-tables');

    // Mobile Orders (Customer)
    Route::post('/checkouts', [MobileCheckoutController::class, 'store'])->middleware('throttle:10,1,mobile-checkouts');
    Route::get('/checkouts/current', [MobileCheckoutController::class, 'current']);
    Route::delete('/checkouts/{checkout}', [MobileCheckoutController::class, 'destroy']);
    Route::post('/orders', [OrderController::class, 'store'])
        ->middleware('throttle:5,1,mobile-orders');
    Route::get('/orders/active', [OrderController::class, 'active']);
    Route::get('/orders/history', [OrderController::class, 'history']);
    Route::get('/orders/{order}/payment-proof', [OrderController::class, 'paymentProof']);
    Route::post('/orders/{order}/pickup', [OrderController::class, 'confirmPickup']);

    // Shop Settings
    Route::get('/shop/qris', [OrderController::class, 'qrisImage']);
});

// Serve storage files with CORS for Flutter Web local development
Route::get('/image/{path}', PublicAssetController::class)
    ->where('path', '.*')->withoutMiddleware(PrivateResponse::class);
