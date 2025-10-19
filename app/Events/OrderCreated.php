<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sự kiện: đơn hàng vừa được tạo.
 * Chỉ mang dữ liệu cần thiết (id, user, total...) để các Listener xử lý.
 */
class OrderCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $orderId,
        public int $userId,
        public float $totalAmount
    ) {}
}
