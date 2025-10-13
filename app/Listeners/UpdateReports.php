<?php

namespace App\Listeners;

use App\Events\OrderCompleted;
use App\Jobs\IncrementReportCounters;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateReports implements ShouldQueue
{
    public function handle(OrderCompleted $event): void
    {
        // đẩy Job để cập nhật thống kê (hàng đợi)
        IncrementReportCounters::dispatch(
            orderId: $event->orderId,
            amount: $event->total
        );
    }
}
