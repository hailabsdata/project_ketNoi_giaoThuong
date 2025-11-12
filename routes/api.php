<?php

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
Route::prefix('stores')->group(function () {
    Route::get('/', [StoreController::class, 'index']); // GET /api/stores
    Route::post('/', [StoreController::class, 'store']); // POST /api/stores
    Route::get('/{store}', [StoreController::class, 'show']); // GET /api/stores/{id}
    Route::put('/{store}', [StoreController::class, 'update']); // PUT /api/stores/{id}
    Route::delete('/{store}', [StoreController::class, 'destroy']); // DELETE /api/stores/{id}
    Route::post('/stores-test', function (Request $request) {
    try {
        $store = \App\Models\Store::create([
            'name' => $request->name,
            'owner_name' => $request->owner_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $store
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});
});