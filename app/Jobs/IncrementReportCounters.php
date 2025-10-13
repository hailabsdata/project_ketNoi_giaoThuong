<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;   // <-- thêm
use Illuminate\Queue\InteractsWithQueue;      // <-- thêm (khuyến nghị)
use Illuminate\Queue\SerializesModels;        // <-- thêm
use Illuminate\Support\Facades\Log;

class IncrementReportCounters implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $orderId,
        public float $amount
    ) {}

    public function handle(): void
    {
        Log::info('Report job running', [
            'order_id' => $this->orderId,
            'amount'   => $this->amount,
        ]);

        // TODO: update bảng report_counters nếu bạn đã tạo
    }
}
