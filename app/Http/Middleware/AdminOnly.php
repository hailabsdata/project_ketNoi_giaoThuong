<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user || !in_array($user->role ?? null, ['admin'], true)) {
            throw new AccessDeniedHttpException('Admin access required');
        }
        return $next($request);
    }
}

