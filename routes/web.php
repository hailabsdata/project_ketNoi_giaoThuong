<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware('basic.env')->get('/', function () {
    return response()->json([
        'service' => 'TradeHub API',
        'env' => config('app.env'),
        'time' => now()->toIso8601String(),
        'docs' => url('/docs'),
        'endpoints' => [
            ['GET','/api/ping','Health check'],
            ['POST','/api/auth/register','Đăng ký (Sanctum session)'],
            ['POST','/api/auth/login','Đăng nhập (Sanctum session)'],
            ['POST','/api/auth/logout','Đăng xuất'],
            ['GET','/api/identity/profile','Thông tin user (auth:sanctum)'],
        ],
    ]);
});
// Route::get('/', function () {
//     return view('welcome');
// });

use Illuminate\Support\Facades\Route;
use App\Models\Product;
use Illuminate\Support\Facades\Http;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/san-pham', function () {
    $products = Product::with(['shop', 'productImages'])
        ->where('status', 'active')
        ->take(12)
        ->get();
    return view('products', compact('products'));
});



Route::get('/momo/callback', function () {
    // MOMO sẽ gọi về đây sau khi thanh toán
    return "Thanh toán thành công! Cảm ơn bạn.";
})->name('momo.callback');
