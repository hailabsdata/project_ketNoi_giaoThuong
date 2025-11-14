<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\IdentityController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\LoginHistoryController;
use App\Http\Controllers\AdminIdentityController;


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


Route::prefix('auth')->group(function () {
    // Rate limit: 5 attempts per minute for sensitive endpoints
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('verify-email', [AuthController::class, 'verifyEmail']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
        Route::post('resend-verification-otp', [AuthController::class, 'resendVerificationOtp']);
    });

    // No rate limit for refresh & logout
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
});

// Login history - user
Route::middleware('auth:api')->get('login-history', [LoginHistoryController::class, 'myHistory']);

// Login history - admin
Route::prefix('admin')->middleware(['auth:api', 'admin'])->group(function () {
    Route::get('login-history', [LoginHistoryController::class, 'adminIndex']);
    Route::get('users/{userId}/login-history', [LoginHistoryController::class, 'adminUserHistory']);
});

// Identity Routes (require auth)
Route::prefix('identity')->middleware('auth:api')->group(function () {
    // User endpoints
    Route::get('profile', [IdentityController::class, 'getProfile']);
    Route::put('profile', [IdentityController::class, 'updateProfile']);
    Route::post('verify-request', [IdentityController::class, 'submitVerifyRequest']);
    Route::get('verify-history', [IdentityController::class, 'getVerifyHistory']);

    // Admin endpoints (require admin role)
    Route::middleware('admin')->group(function () {
        Route::get('verify-requests', [AdminIdentityController::class, 'getVerifyRequests']);
        Route::get('verify-requests/{id}', [AdminIdentityController::class, 'getVerifyRequest']);
        Route::put('verify-request/{id}/approve', [IdentityController::class, 'approveVerifyRequest']);
        Route::put('verify-request/{id}/reject', [IdentityController::class, 'rejectVerifyRequest']);
    });
});

// Moderation Routes (require auth)
Route::prefix('moderation')->middleware('auth:api')->group(function () {
    // User endpoints
    Route::post('report', [ModerationController::class, 'report']);
    Route::get('my-reports', [ModerationController::class, 'myReports']);

    // Admin endpoints (require admin role)
    Route::middleware('admin')->group(function () {
        Route::get('reports', [ModerationController::class, 'getReports']);
        Route::get('reports/{id}', [ModerationController::class, 'getReport']);
        Route::put('reports/{id}/resolve', [ModerationController::class, 'resolveReport']);
        Route::delete('reports/{id}', [ModerationController::class, 'deleteReport']);
    });
});
