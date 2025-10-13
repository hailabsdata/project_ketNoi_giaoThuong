<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class RequestCorrelation
{
    public function handle($request, Closure $next)
    {
        // Tạo/Xài Request ID
        $rid = $request->header('X-Request-ID') ?: (string) Str::uuid();

        // Tạo 'traceparent' đơn giản chuẩn W3C (đủ dùng để correlate)
        $trace = $request->header('traceparent')
            ?: sprintf('00-%s-0000000000000000-01', str_replace('-', '', (string) Str::uuid()));

        // Gắn vào request để các phần sau dùng
        $request->headers->set('X-Request-ID', $rid);
        $request->headers->set('traceparent',  $trace);

        // Ghi log qua channel mặc định (tránh lỗi do chưa tạo channel riêng)
        Log::info('IN', ['path' => $request->path(), 'rid' => $rid, 'trace' => $trace]);

        $resp = $next($request);

        // Gắn vào response
        $resp->headers->set('X-Request-ID', $rid);
        $resp->headers->set('traceparent',  $trace);

        Log::info('OUT', ['status' => $resp->getStatusCode(), 'rid' => $rid]);

        return $resp;
    }
}
