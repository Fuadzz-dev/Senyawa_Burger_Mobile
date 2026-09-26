<?php

use App\Http\Controllers\CustomerApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('customer')->group(function () {
    Route::get('/menus', [CustomerApiController::class, 'index']);
    Route::get('/menus/{id}', [CustomerApiController::class, 'show']);
    Route::post('/checkout', [CustomerApiController::class, 'checkout']);
    Route::get('/orders/{id}', [CustomerApiController::class, 'orderStatus']);
    Route::get('/orders', [CustomerApiController::class, 'orders']);
});
