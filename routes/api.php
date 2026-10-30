<?php

use App\Http\Controllers\Api\V1;
use Illuminate\Support\Facades\Route;

// Prefix: /api/v1 (see bootstrap/app.php). Auth: Sanctum bearer tokens.

Route::post('/auth/register', [V1\AuthController::class, 'register'])->middleware('throttle:auth');
Route::post('/auth/login', [V1\AuthController::class, 'login'])->middleware('throttle:auth');

Route::get('/categories', [V1\CatalogController::class, 'categories']);
Route::get('/products', [V1\CatalogController::class, 'products']);
Route::get('/products/{product:slug}', [V1\CatalogController::class, 'product']);
Route::get('/products/{product:slug}/reviews', [V1\ReviewController::class, 'index']);
Route::post('/cart/quote', [V1\CartController::class, 'quote']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [V1\AuthController::class, 'me']);
    Route::post('/auth/logout', [V1\AuthController::class, 'logout']);

    Route::get('/orders', [V1\OrderController::class, 'index']);
    Route::post('/orders', [V1\OrderController::class, 'store'])->middleware(['customer', 'throttle:checkout']);
    Route::get('/orders/{order}', [V1\OrderController::class, 'show']);
    Route::patch('/orders/{order}/status', [V1\OrderController::class, 'updateStatus']);
    Route::post('/orders/{order}/cancel', [V1\OrderController::class, 'cancel']);

    Route::post('/products/{product:slug}/reviews', [V1\ReviewController::class, 'store'])->middleware('customer');

    Route::post('/promotions/validate', [V1\PromotionController::class, 'validateCode']);
    Route::middleware('can:manage-promotions')->group(function () {
        Route::get('/promotions', [V1\PromotionController::class, 'index']);
        Route::post('/promotions', [V1\PromotionController::class, 'store']);
    });

    Route::middleware('can:manage-catalog')->group(function () {
        Route::post('/products', [V1\CatalogController::class, 'store']);
        Route::put('/products/{product:slug}', [V1\CatalogController::class, 'update']);
        Route::delete('/products/{product:slug}', [V1\CatalogController::class, 'destroy']);
    });

    Route::middleware('can:manage-customers')->group(function () {
        Route::get('/customers', [V1\CustomerController::class, 'index']);
        Route::get('/customers/{user}', [V1\CustomerController::class, 'show']);
    });

    Route::middleware('can:view-inventory')->group(function () {
        Route::get('/inventory', [V1\InventoryController::class, 'index']);
        Route::get('/inventory/{item}/movements', [V1\InventoryController::class, 'movements']);
        Route::post('/inventory/{item}/adjustments', [V1\InventoryController::class, 'adjust']);
    });

    Route::get('/analytics/{report}', V1\AnalyticsController::class)
        ->whereIn('report', ['sales', 'products', 'customers', 'forecast'])
        ->middleware('can:view-analytics');
});
