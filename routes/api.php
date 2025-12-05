<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Models\User;
use App\Events\UserRegistered;
use App\Http\Controllers\Reports\ReportsController;
use App\Http\Controllers\Tracking\TrackController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\IdentityController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\LoginHistoryController;
use App\Http\Controllers\AdminIdentityController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\DataExportController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\PromotionController;
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


// Public subscription plans
Route::get('plans', [PlanController::class, 'index']);
Route::get('plans/{id}', [PlanController::class, 'show'])->whereNumber('id');

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

// Notifications (require auth)
Route::prefix('notifications')->middleware('auth:api')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('{id}', [NotificationController::class, 'show'])->whereNumber('id');
    Route::put('{id}/read', [NotificationController::class, 'markAsRead'])->whereNumber('id');
    Route::put('read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('delete-all', [NotificationController::class, 'destroyAll']);
    Route::delete('{id}', [NotificationController::class, 'destroy'])->whereNumber('id');
});

// Subscriptions (require auth)
Route::prefix('subscriptions')->middleware('auth:api')->group(function () {
    Route::post('/', [SubscriptionController::class, 'subscribe']);
    Route::put('{id}/renew', [SubscriptionController::class, 'renew'])->whereNumber('id');
    Route::get('current', [SubscriptionController::class, 'current']);
    Route::get('history', [SubscriptionController::class, 'history']);
    Route::delete('{id}/cancel', [SubscriptionController::class, 'cancel'])->whereNumber('id');
});

// Data export (require auth)
Route::prefix('data/export')->middleware('auth:api')->group(function () {
    Route::post('request', [DataExportController::class, 'requestExport']);
    Route::get('status/{id}', [DataExportController::class, 'status'])->whereNumber('id');
    Route::get('download/{id}', [DataExportController::class, 'download'])->whereNumber('id');
    Route::delete('cancel/{id}', [DataExportController::class, 'cancel'])->whereNumber('id');
    Route::get('history', [DataExportController::class, 'history']);
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
// Orders API - Quản lý đơn hàng (require auth)
Route::prefix('orders')->middleware('auth:api')->group(function () {
    Route::get('/', [OrderController::class, 'index']);           // Danh sách đơn hàng
    Route::get('/{id}', [OrderController::class, 'show']);        // Chi tiết đơn hàng
    Route::post('/', [OrderController::class, 'store']);          // Tạo đơn hàng mới
    Route::put('/{id}', [OrderController::class, 'update']);      // Cập nhật đơn hàng
    Route::delete('/{id}', [OrderController::class, 'destroy']);  // Hủy đơn hàng
});



// Reviews API - Hệ thống đánh giá
Route::prefix('reviews')->group(function () {
    // Public routes - không cần auth
    Route::get('/', [ReviewController::class, 'index']);           // Danh sách đánh giá (filter, sort, pagination)
    Route::get('/summary', [ReviewController::class, 'getSummary']); // Thống kê rating
    Route::get('/{id}', [ReviewController::class, 'show']);        // Chi tiết đánh giá
    
    // Protected routes - cần auth
    Route::middleware('auth:api')->group(function () {
        Route::get('/my-reviews', [ReviewController::class, 'myReviews']); // Đánh giá của tôi
        Route::post('/', [ReviewController::class, 'store']);          // Tạo đánh giá mới
        Route::put('/{id}', [ReviewController::class, 'update']);      // Cập nhật đánh giá
        Route::delete('/{id}', [ReviewController::class, 'destroy']);  // Xóa đánh giá
        
        // Mark as helpful
        Route::post('/{id}/helpful', [ReviewController::class, 'markAsHelpful']);
        Route::delete('/{id}/helpful', [ReviewController::class, 'unmarkAsHelpful']);
        
        // Seller reply
        Route::post('/{id}/reply', [ReviewController::class, 'addSellerReply']);
    });
});



// Payments API - Thanh toán
Route::prefix('payments')->group(function () {
    // Public routes - Callbacks từ payment gateways
    Route::post('/vnpay/callback', [PaymentController::class, 'vnpayCallback']);
    Route::post('/momo/callback', [PaymentController::class, 'momoCallback']);
    Route::post('/zalopay/callback', [PaymentController::class, 'zalopayCallback']);
    
    // Protected routes - Cần authentication
    Route::middleware('auth:api')->group(function () {
        Route::get('/', [PaymentController::class, 'index']);
        Route::get('/my-payments', [PaymentController::class, 'myPayments']);
        Route::get('/{id}', [PaymentController::class, 'show']);
        Route::post('/', [PaymentController::class, 'store']);
        Route::post('/{id}/refund', [PaymentController::class, 'refund']);
        Route::post('/{id}/cancel', [PaymentController::class, 'cancel']);
    });
});

// Shops API - Quản lý gian hàng
Route::prefix('shops')->group(function () {
    Route::get('/', [\App\Http\Controllers\ShopController::class, 'index']);
    Route::get('/{shop}', [\App\Http\Controllers\ShopController::class, 'show']);
    
    Route::middleware('auth:api')->group(function () {
        Route::post('/', [\App\Http\Controllers\ShopController::class, 'store']);
        Route::put('/{shop}', [\App\Http\Controllers\ShopController::class, 'update']);
        Route::delete('/{shop}', [\App\Http\Controllers\ShopController::class, 'destroy']);
    });
    
    // Categories của shop (nested routes)
    Route::prefix('{shop}/categories')->group(function () {
        // Public - xem categories của shop
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/simple-list', [CategoryController::class, 'simpleList']);
        Route::get('/{category}', [CategoryController::class, 'show']);
        
        // Seller - tạo/sửa/xóa categories của shop mình
        Route::middleware('auth:api')->group(function () {
            Route::post('/', [CategoryController::class, 'store']);
            Route::put('/{category}', [CategoryController::class, 'update']);
            Route::delete('/{category}', [CategoryController::class, 'destroy']);
        });
    });
});

// Global Categories - Xem tất cả categories từ mọi shops (cho trang chủ, search)
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'allCategories']); // Tất cả categories
    Route::get('/simple-list', [CategoryController::class, 'allCategoriesSimple']); // Dropdown tất cả
});

Route::prefix('listings')->group(function () {
    Route::get('/', [ListingController::class, 'index']);      // Danh sách (có lọc, tìm kiếm, phân trang)
    Route::post('/', [ListingController::class, 'store']);     // Thêm mới
    Route::get('/{listing}', [ListingController::class, 'show']); // Xem chi tiết
    Route::put('/{listing}', [ListingController::class, 'update']); // Cập nhật
    Route::delete('/{listing}', [ListingController::class, 'destroy']); // Xóa
});

Route::prefix('promotion')->middleware('auth:api')->group(function () {
    Route::get('/', [PromotionController::class, 'index']);
    Route::get('/active', [PromotionController::class, 'activePromotions']);
    Route::post('/', [PromotionController::class, 'store']);
    Route::get('/{id}', [PromotionController::class, 'show']);
    Route::put('/{id}', [PromotionController::class, 'update']);
    Route::patch('/{id}/featured', [PromotionController::class, 'updateFeatured']);
    Route::delete('/{id}', [PromotionController::class, 'destroy']);
});

// Discovery & Social Features
Route::prefix('discovery')->group(function () {
    Route::get('search', [\App\Http\Controllers\Discovery\DiscoveryController::class, 'search']);
});

// Bookmarks (require auth)
Route::prefix('bookmarks')->middleware('auth:api')->group(function () {
    Route::get('/', [\App\Http\Controllers\Discovery\BookmarkController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Discovery\BookmarkController::class, 'store']);
    Route::delete('/{listing_id}', [\App\Http\Controllers\Discovery\BookmarkController::class, 'destroy']);
});

// Listing Social Features (require auth)
Route::prefix('listings')->middleware('auth:api')->group(function () {
    Route::post('/{listing}/like', [\App\Http\Controllers\Discovery\SocialController::class, 'like']);
    Route::delete('/{listing}/like', [\App\Http\Controllers\Discovery\SocialController::class, 'unlike']);
    Route::post('/{listing}/comments', [\App\Http\Controllers\Discovery\SocialController::class, 'comment']);
    Route::get('/{listing}/comments', [\App\Http\Controllers\Discovery\SocialController::class, 'getComments']);
});

// Chat (require auth)
Route::prefix('chat')->middleware('auth:api')->group(function () {
    Route::get('conversations', [\App\Http\Controllers\Discovery\ChatController::class, 'conversations']);
    Route::get('messages/{user_id}', [\App\Http\Controllers\Discovery\ChatController::class, 'messages']);
    Route::post('messages', [\App\Http\Controllers\Discovery\ChatController::class, 'send']);
    Route::put('messages/{user_id}/read', [\App\Http\Controllers\Discovery\ChatController::class, 'markAsRead']);
});

// Inquiries
Route::post('inquiries', [\App\Http\Controllers\Discovery\InquiryController::class, 'store']);
Route::get('inquiries', [\App\Http\Controllers\Discovery\InquiryController::class, 'index'])->middleware('auth:api');

// Auctions
Route::prefix('auctions')->group(function () {
    Route::get('/', [\App\Http\Controllers\Discovery\AuctionController::class, 'index']);
    Route::get('/{auction}', [\App\Http\Controllers\Discovery\AuctionController::class, 'show']);
    
    Route::middleware('auth:api')->group(function () {
        Route::post('/', [\App\Http\Controllers\Discovery\AuctionController::class, 'store']);
        Route::put('/{auction}', [\App\Http\Controllers\Discovery\AuctionController::class, 'update']);
        Route::delete('/{auction}', [\App\Http\Controllers\Discovery\AuctionController::class, 'destroy']);
        Route::post('/{auction}/bids', [\App\Http\Controllers\Discovery\AuctionController::class, 'placeBid']);
        Route::get('/{auction}/bids', [\App\Http\Controllers\Discovery\AuctionController::class, 'getBids']);
        Route::get('/my-bids', [\App\Http\Controllers\Discovery\AuctionController::class, 'myBids']);
    });
});

// Support & FAQ
Route::prefix('faqs')->group(function () {
    Route::get('/', [\App\Http\Controllers\Discovery\SupportController::class, 'faqs']);
});

Route::prefix('support')->middleware('auth:api')->group(function () {
    Route::get('tickets', [\App\Http\Controllers\Discovery\SupportController::class, 'tickets']);
    Route::post('tickets', [\App\Http\Controllers\Discovery\SupportController::class, 'createTicket']);
    Route::get('tickets/{ticket}', [\App\Http\Controllers\Discovery\SupportController::class, 'showTicket']);
    Route::post('tickets/{ticket}/messages', [\App\Http\Controllers\Discovery\SupportController::class, 'replyTicket']);
    Route::put('tickets/{ticket}/close', [\App\Http\Controllers\Discovery\SupportController::class, 'closeTicket']);
});

// Statistics (require auth)
Route::prefix('stats')->middleware('auth:api')->group(function () {
    Route::get('overview', [\App\Http\Controllers\Api\ReportController::class, 'overview']);
    Route::get('views', [\App\Http\Controllers\Api\ReportController::class, 'views']);
    Route::get('revenue', [\App\Http\Controllers\Api\ReportController::class, 'revenue']);
    Route::get('promotions', [\App\Http\Controllers\Api\ReportController::class, 'promotions']);
});

// Admin routes
Route::prefix('admin')->middleware(['auth:api', 'admin'])->group(function () {
    Route::get('users', [UserController::class, 'index']);
    Route::put('listings/{listing}/approve', [ListingController::class, 'approve']);
});

// Fallback route - phải đặt cuối cùng
Route::fallback(fn () => response()->json(['message' => 'Not Found'], 404));
