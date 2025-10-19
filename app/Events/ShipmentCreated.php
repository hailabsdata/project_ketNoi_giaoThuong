<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sự kiện: tạo vận đơn/thông tin giao hàng.
 */
class ShipmentCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $shipmentId,
        public int $orderId,
        public string $carrier,
        public ?string $trackingNo = null
    ) {}
}
