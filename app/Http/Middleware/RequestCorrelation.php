<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class RequestCorrelation
{
    public function handle($request, Closure $next)
    {
        $rid = $request->header('X-Request-ID') ?: (string) Str::uuid();
        $trace = $request->header('traceparent')
            ?: sprintf('00-%s-0000000000000000-01', str_replace('-', '', (string) Str::uuid()));

        $request->headers->set('X-Request-ID', $rid);
        $request->headers->set('traceparent',  $trace);

        Log::info('IN', ['path' => $request->path(), 'rid' => $rid, 'trace' => $trace]);

        $resp = $next($request);

        $resp->headers->set('X-Request-ID', $rid);
        $resp->headers->set('traceparent',  $trace);

        Log::info('OUT', ['status' => $resp->getStatusCode(), 'rid' => $rid]);

        return $resp;
    }
}
