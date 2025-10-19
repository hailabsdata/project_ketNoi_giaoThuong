<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sự kiện: thanh toán thành công cho 1 đơn.
 */
class PaymentSucceeded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $orderId,
        public string $transactionId,
        public float $paidAmount
    ) {}
}
