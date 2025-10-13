<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        // ...
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        // ...
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // place for Sentry/Bugsnag/etc if needed
        });
    }

    public function render($request, Throwable $e)
    {
        if ($request->expectsJson()) {

            // 1) Validation → 422 + fields
            if ($e instanceof ValidationException) {
                return response()->json([
                    'error'      => 'ValidationException',
                    'message'    => $e->getMessage(),
                    'fields'     => $e->errors(),
                    'request_id' => $request->header('X-Request-ID'),
                ], 422);
            }

            // 2) Map status code chuẩn theo loại ngoại lệ
            $status = match (true) {
                $e instanceof HttpExceptionInterface         => $e->getStatusCode(), // mọi 4xx/5xx từ Symfony
                $e instanceof AuthenticationException        => 401,
                $e instanceof AuthorizationException         => 403,
                $e instanceof ModelNotFoundException         => 404,
                $e instanceof NotFoundHttpException          => 404,
                $e instanceof MethodNotAllowedHttpException  => 405,
                $e instanceof ThrottleRequestsException      => 429,
                default                                      => 500,
            };

            // 3) Ẩn message khi production (APP_DEBUG=false) với lỗi 5xx
            $message = config('app.debug')
                ? $e->getMessage()
                : ($status >= 500 ? 'Server Error' : $e->getMessage());

            return response()->json([
                'error'      => class_basename($e),
                'message'    => $message,
                'status'     => $status,
                'request_id' => $request->header('X-Request-ID'),
            ], $status);
        }

        return parent::render($request, $e);
    }
}
