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

Route::get('/', function () {
    return view('welcome');
});


use App\Http\Controllers\Auth\AuthController;

Route::prefix('api')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login',    [AuthController::class, 'login']);
    Route::post('/auth/logout',   [AuthController::class, 'logout']);
});


Route::middleware('basic.env')->get('/', function () {
    return response()->json([
        'service' => 'TradeHub API',
        'env' => config('app.env'),
        'time' => now()->toIso8601String(),
        'docs' => url('/api/documentation'),
        'endpoints' => [
            ['GET','/api/ping','Health check'],
            ['POST','/api/auth/register','Đăng ký (Sanctum session)'],
            ['POST','/api/auth/login','Đăng nhập (Sanctum session)'],
            ['POST','/api/auth/logout','Đăng xuất'],
            ['GET','/api/identity/profile','Thông tin user (auth:sanctum)'],
        ],
    ]);
});
Route::middleware('basic.env')->get('/docs', fn () => redirect('/api/documentation'));

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


Route::post('/login', function (Request $request) {
    $data = $request->validate([
        'email'    => ['required','email'],
        'password' => ['required'],
        'remember' => ['boolean'],
    ]);

    if (! Auth::attempt(['email'=>$data['email'], 'password'=>$data['password']], $request->boolean('remember'))) {
        return response()->json(['message'=>'Thông tin đăng nhập không đúng'], 422);
    }

    $request->session()->regenerate();
    return response()->json(['user' => Auth::user()]);
});

Route::post('/logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return response()->noContent();
});