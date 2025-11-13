<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\StoreController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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



// Category routes
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/simple', [CategoryController::class, 'simpleList']); // For dropdowns
    Route::post('/', [CategoryController::class, 'store']);
    Route::get('/{category}', [CategoryController::class, 'show']);
    Route::put('/{category}', [CategoryController::class, 'update']);
    Route::delete('/{category}', [CategoryController::class, 'destroy']);
});


Route::prefix('stores')->group(function () {
    Route::get('/', [StoreController::class, 'index']); // GET /api/stores
    Route::post('/', [StoreController::class, 'store']); // POST /api/stores
    Route::get('/{store}', [StoreController::class, 'show']); // GET /api/stores/{id}
    Route::put('/{store}', [StoreController::class, 'update']); // PUT /api/stores/{id}
    Route::delete('/{store}', [StoreController::class, 'destroy']); // DELETE /api/stores/{id}
    
});

// Listing routes
Route::prefix('listings')->group(function () {
    Route::get('/', [ListingController::class, 'index']);
    Route::post('/', [ListingController::class, 'store']);
    Route::get('/{listing}', [ListingController::class, 'show']);
    Route::put('/{listing}', [ListingController::class, 'update']);
    Route::delete('/{listing}', [ListingController::class, 'destroy']);
});
// Promotion routes
Route::prefix('media/promotion')->group(function () {
    Route::get('/', [PromotionController::class, 'index']); // GET /api/media/promotion
    Route::get('/active', [PromotionController::class, 'activePromotions']); // GET /api/media/promotion/active
    Route::post('/', [PromotionController::class, 'store']); // POST /api/media/promotion
    Route::get('/{id}', [PromotionController::class, 'show']); // GET /api/media/promotion/{id}
    Route::put('/{id}', [PromotionController::class, 'update']); // PUT /api/media/promotion/{id}
    Route::delete('/{id}', [PromotionController::class, 'destroy']); // DELETE /api/media/promotion/{id}
    Route::patch('/{id}/featured', [PromotionController::class, 'updateFeatured']); // PATCH /api/media/promotion/{id}/featured
});