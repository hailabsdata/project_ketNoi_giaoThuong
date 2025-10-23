// app/Events/OrderCreated.php
<?php
class OrderCreated
{
    public function __construct(
        public int $orderId,
        public int $userId,
        public float $totalAmount
    ) {}
}