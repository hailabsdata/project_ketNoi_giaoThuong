<?php

namespace App\Http\Middleware;

use App\Support\Correlation;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Middleware đầu vào:
 * - Nhận X-Request-Id / X-Correlation-Id nếu client gửi, hoặc tự sinh (UUIDv4).
 * - Đặt vào Correlation + Log::withContext để mọi log trong request có ID.
 * - Thêm header X-Request-Id / X-Correlation-Id vào response trả về.
 */
class RequestCorrelation
{
    public function handle(Request $request, Closure $next)
    {
        // 1) Lấy từ header nếu có, nếu không sinh mới
        $reqId = $request->headers->get('X-Request-Id') ?? (string) Str::uuid();
        $corrId = $request->headers->get('X-Correlation-Id') ?? $reqId;

        // 2) Lưu vào Correlation (toàn app dùng)
        Correlation::set($reqId, $corrId);

        // 3) Đưa vào log context (mọi Log::info/err... sẽ có kèm ID)
        Log::withContext([
            'request_id'     => $reqId,
            'correlation_id' => $corrId,
            'path'           => $request->path(),
            'method'         => $request->method(),
        ]);

        // 4) Chạy tiếp chuỗi middleware/controller
        $response = $next($request);

        // 5) Gắn header ra cho client/dev tools
        $response->headers->set('X-Request-Id', $reqId);
        $response->headers->set('X-Correlation-Id', $corrId);

        return $response;
    }
}
