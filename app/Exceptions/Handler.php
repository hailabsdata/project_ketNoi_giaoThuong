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
use App\Support\Correlation;

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
    // Lấy ID từ Correlation (đã được middleware RequestCorrelation set ở đầu request)
    $reqId  = Correlation::requestId();
    $corrId = Correlation::correlationId();

    if ($request->expectsJson()) {

        // 1) Validation → 422 + fields
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            $json = response()->json([
                'error'           => 'ValidationException',
                'message'         => $e->getMessage(),
                'fields'          => $e->errors(),
                'request_id'      => $reqId,   // dùng Correlation thay vì đọc header
                'correlation_id'  => $corrId,
            ], 422);

            // Gắn header ID cho client/devtools
            return $json->header('X-Request-Id', $reqId)
                        ->header('X-Correlation-Id', $corrId);
        }

        // 2) Map status code chuẩn theo loại ngoại lệ
        $status = match (true) {
            $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface => $e->getStatusCode(),
            $e instanceof \Illuminate\Auth\AuthenticationException                       => 401,
            $e instanceof \Illuminate\Auth\Access\AuthorizationException                 => 403,
            $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException           => 404,
            $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException  => 404,
            $e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException => 405,
            $e instanceof \Illuminate\Http\Exceptions\ThrottleRequestsException          => 429,
            default                                                                      => 500,
        };

        // 3) Ẩn message khi production (APP_DEBUG=false) với lỗi 5xx
        $message = config('app.debug')
            ? $e->getMessage()
            : ($status >= 500 ? 'Server Error' : $e->getMessage());

        $json = response()->json([
            'error'           => class_basename($e),
            'message'         => $message,
            'status'          => $status,
            'request_id'      => $reqId,
            'correlation_id'  => $corrId,
        ], $status);

        // Trả kèm header ID
        return $json->header('X-Request-Id', $reqId)
                    ->header('X-Correlation-Id', $corrId);
    }

    // Non-JSON: dùng render mặc định rồi gắn header
    $response = parent::render($request, $e);
    $response->headers->set('X-Request-Id', $reqId);
    $response->headers->set('X-Correlation-Id', $corrId);
    return $response;
}

}
