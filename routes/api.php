<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Models\User;
use App\Events\UserRegistered;
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


Route::middleware(['force.json', 'throttle:60,1'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
});


Route::get('/_demo/validation', function (Request $r) {
    $r->validate(['email' => 'required|email']);
});

Route::get('/_demo/not-found', function () {
    abort(404);
});

Route::get('/demo/events/welcome/{id}', function ($id) {
    $user = User::findOrFail($id);    // lấy user có sẵn trong DB
    event(new UserRegistered($user)); // bắn sự kiện -> listener -> notif + job
    return response()->json(['ok' => true]);
});

// --- Admin APIs ---
use Illuminate\Support\Facades\Route as RouteFacade;
Route::middleware(['auth:sanctum', 'admin', 'force.json', 'throttle:60,1'])
    ->prefix('admin')
    ->group(function () {
        // User management
        RouteFacade::get('/users', [\App\Http\Controllers\Admin\UserAdminController::class, 'index']);
        RouteFacade::patch('/users/{id}/role', [\App\Http\Controllers\Admin\UserAdminController::class, 'updateRole']);
        RouteFacade::patch('/users/{id}/status', [\App\Http\Controllers\Admin\UserAdminController::class, 'updateStatus']);

        // Content moderation: trade posts
        RouteFacade::get('/content/trade-posts', [\App\Http\Controllers\Admin\ContentAdminController::class, 'listTradePosts']);
        RouteFacade::patch('/content/trade-posts/{id}/approve', [\App\Http\Controllers\Admin\ContentAdminController::class, 'approveTradePost']);
        RouteFacade::patch('/content/trade-posts/{id}/reject', [\App\Http\Controllers\Admin\ContentAdminController::class, 'rejectTradePost']);

        // Content moderation: products
        RouteFacade::get('/content/products', [\App\Http\Controllers\Admin\ContentAdminController::class, 'listProducts']);
        RouteFacade::patch('/content/products/{id}/status', [\App\Http\Controllers\Admin\ContentAdminController::class, 'updateProductStatus']);

        // Complaints
        RouteFacade::get('/complaints', [\App\Http\Controllers\Admin\ComplaintAdminController::class, 'index']);
        RouteFacade::get('/complaints/{id}', [\App\Http\Controllers\Admin\ComplaintAdminController::class, 'show']);
        RouteFacade::patch('/complaints/{id}/resolve', [\App\Http\Controllers\Admin\ComplaintAdminController::class, 'resolve']);
        RouteFacade::patch('/complaints/{id}/reject', [\App\Http\Controllers\Admin\ComplaintAdminController::class, 'reject']);
    });
