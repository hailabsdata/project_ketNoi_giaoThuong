<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DuplicateListingController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\PromotionCostEstimationController;
use App\Http\Controllers\StoreController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/simple', [CategoryController::class, 'simpleList']);
    Route::post('/', [CategoryController::class, 'store']);
    Route::get('/{category}', [CategoryController::class, 'show']);
    Route::put('/{category}', [CategoryController::class, 'update']);
    Route::delete('/{category}', [CategoryController::class, 'destroy']);
});

Route::prefix('stores')->group(function () {
    Route::get('/', [StoreController::class, 'index']);
    Route::post('/', [StoreController::class, 'store']);
    Route::get('/{store}', [StoreController::class, 'show']);
    Route::put('/{store}', [StoreController::class, 'update']);
    Route::delete('/{store}', [StoreController::class, 'destroy']);
});

Route::prefix('listings')->group(function () {
    Route::get('/', [ListingController::class, 'index']);
    Route::post('/', [ListingController::class, 'store']);
    Route::get('/{listing}', [ListingController::class, 'show']);
    Route::put('/{listing}', [ListingController::class, 'update']);
    Route::delete('/{listing}', [ListingController::class, 'destroy']);
});

Route::prefix('media/promotion')->group(function () {
    Route::get('/', [PromotionController::class, 'index']);
    Route::get('/active', [PromotionController::class, 'activePromotions']);
    Route::post('/', [PromotionController::class, 'store']);
    Route::get('/{id}', [PromotionController::class, 'show']);
    Route::put('/{id}', [PromotionController::class, 'update']);
    Route::delete('/{id}', [PromotionController::class, 'destroy']);
    Route::patch('/{id}/featured', [PromotionController::class, 'updateFeatured']);
});

Route::prefix('duplicate/listing')->group(function () {
    Route::get('/', [DuplicateListingController::class, 'index']);
    Route::get('/{group_id}', [DuplicateListingController::class, 'show']);
    Route::post('/', [DuplicateListingController::class, 'store']);
    Route::delete('/{group_id}', [DuplicateListingController::class, 'destroy']);
    Route::patch('/{group_id}/status', [DuplicateListingController::class, 'updateStatus']);
    Route::post('/auto-detect', [DuplicateListingController::class, 'autoDetect']);
});

Route::prefix('promotion/cost-estimation')->group(function () {
    Route::get('/', [PromotionCostEstimationController::class, 'index']);
    Route::get('/{id}', [PromotionCostEstimationController::class, 'show']);
    Route::post('/', [PromotionCostEstimationController::class, 'store']);
    Route::put('/{id}', [PromotionCostEstimationController::class, 'update']);
    Route::delete('/{id}', [PromotionCostEstimationController::class, 'destroy']);
    Route::patch('/{id}/status', [PromotionCostEstimationController::class, 'updateStatus']);
    Route::post('/quick-calculate', [PromotionCostEstimationController::class, 'quickCalculate']);
});
