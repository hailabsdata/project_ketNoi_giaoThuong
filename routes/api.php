<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\PaymentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



Route::prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index']);      // Lấy tất cả
    Route::get('/{id}', [OrderController::class, 'show']);   // Lấy theo ID
    Route::post('/', [OrderController::class, 'store']);          // POST create
    Route::put('/{id}', [OrderController::class, 'update']);      // PUT update
    Route::delete('/{id}', [OrderController::class, 'destroy']);  // DELETE cancel
});



Route::prefix('reviews')->group(function () {
    Route::get('/', [ReviewController::class, 'index']);           // GET all
    Route::get('/{id}', [ReviewController::class, 'show']);        // GET by id
    Route::post('/', [ReviewController::class, 'store']);          // POST create
    Route::put('/{id}', [ReviewController::class, 'update']);      // PUT update
    Route::delete('/{id}', [ReviewController::class, 'destroy']);  // DELETE
});



Route::prefix('payments')->group(function () {
    Route::get('/', [PaymentController::class, 'index']);
    Route::get('/{id}', [PaymentController::class, 'show']);
    Route::post('/', [PaymentController::class, 'store']);
});
