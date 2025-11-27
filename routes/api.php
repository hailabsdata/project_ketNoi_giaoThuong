<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Discovery\DiscoveryController;
use App\Http\Controllers\Discovery\BookmarkController;
use App\Http\Controllers\Discovery\ChatController;
use App\Http\Controllers\Discovery\InquiryController;
use App\Http\Controllers\Discovery\AuctionController;
use App\Http\Controllers\Discovery\SocialController;
use App\Http\Controllers\Discovery\SupportController;
use App\Http\Controllers\Auth\SocialLoginController;
use App\Http\Controllers\IdentityController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Các route API chính cho module Discovery + Interactions (Duy).
|
*/

Route::get('/ping', function () {
    return response()->json([
        'ok' => true,
        'time' => now()->toDateTimeString(),
    ]);
});

// ==== BA 3.4 / 3.6 / 3.8 — Discovery + Social + Support (Duy) ====

// Public discovery
Route::get('/discovery/search',  [DiscoveryController::class, 'search']);
Route::get('/discovery/nearby',  [DiscoveryController::class, 'nearby']);

// Public inquiry (BA 3.4)
Route::post('/inquiries', [InquiryController::class, 'store'])->middleware('throttle:20,1');

// Social login (Google)
Route::post('/auth/social/google', [SocialLoginController::class, 'google']);

Route::middleware(['auth:sanctum'])->group(function () {
    // Bookmarks
    Route::get('/discovery/bookmarks', [DiscoveryController::class, 'bookmarks']);
    Route::post('/bookmarks',          [BookmarkController::class, 'store']);
    Route::delete('/bookmarks/{id}',   [BookmarkController::class, 'destroy']);

    // Chat
    Route::get('/chat/messages',  [ChatController::class, 'index']);
    Route::post('/chat/messages', [ChatController::class, 'store']);

    // Auctions
    Route::get('/auctions',             [AuctionController::class, 'index']);
    Route::get('/auctions/{id}',        [AuctionController::class, 'show']);
    Route::post('/auctions/{id}/bids',  [AuctionController::class, 'bid']);

    // Social interactions
    Route::post('/social/listings/{id}/like',    [SocialController::class, 'like']);
    Route::delete('/social/listings/{id}/like',  [SocialController::class, 'unlike']);
    Route::get('/social/listings/{id}/comments', [SocialController::class, 'comments']);
    Route::post('/social/listings/{id}/comments',[SocialController::class, 'storeComment']);

    // Support / Tickets
    Route::get('/support/faqs',                 [SupportController::class, 'faqs']);
    Route::post('/support/tickets',             [SupportController::class, 'createTicket']);
    Route::get('/support/tickets',              [SupportController::class, 'myTickets']);
    Route::get('/support/tickets/{id}',         [SupportController::class, 'show']);
    Route::post('/support/tickets/{id}/reply',  [SupportController::class, 'reply']);
});


Route::middleware(['auth:sanctum'])->get('/identity/profile', [IdentityController::class, 'profile']);
Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
    return $request->user();
});



Route::fallback(fn () => response()->json(['message' => 'Not Found'], 404));