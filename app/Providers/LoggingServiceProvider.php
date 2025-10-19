<?php

namespace App\Providers;

use App\Support\Correlation;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class LoggingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // 1) Nhúng ID vào payload của mọi Job khi dispatch
        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            return [
                'request_id'     => Correlation::requestId(),
                'correlation_id' => Correlation::correlationId(),
            ];
        });

        // 2) Trước khi xử lý Job: khôi phục Correlation + Log context
        Queue::before(function (JobProcessing $event) {
            $p      = $event->job->payload();
            $reqId  = $p['request_id']     ?? (string) Str::uuid();
            $corrId = $p['correlation_id'] ?? $reqId;

            Correlation::set($reqId, $corrId);

            Log::withContext([
                'request_id'     => $reqId,
                'correlation_id' => $corrId,
                'queue'          => $event->connectionName . ':' . $event->job->getQueue(),
                'job_name'       => $p['displayName'] ?? get_class($event->job),
            ]);
        });

        // 3) Sau khi xử lý Job: giữ nguyên context nếu còn log tiếp
        Queue::after(function (JobProcessed $event) {});

        // 4) Outbound HTTP: macro Http::obs() tự gắn ID cho mọi request đi
        Http::macro('obs', function () {
            return Http::beforeSending(function (HttpRequest $req, array $options) {
                $req->withHeaders([
                    'X-Request-Id'     => Correlation::requestId(),
                    'X-Correlation-Id' => Correlation::correlationId(),
                ]);
            })->throw();
        });

        
    }
}
