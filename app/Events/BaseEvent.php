<?php
// app/Events/BaseEvent.php
namespace App\Events;

use App\Support\Correlation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

abstract class BaseEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $requestId;
    public string $correlationId;
    public string $eventId;
    public array $metadata;

    public function __construct()
    {
        $this->eventId = (string) \Illuminate\Support\Str::uuid();
        $this->requestId = Correlation::requestId();
        $this->correlationId = Correlation::correlationId();
        $this->metadata = [
            'user_id' => auth()->id(),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public function getTraceContext(): array
    {
        return [
            'event_id' => $this->eventId,
            'request_id' => $this->requestId,
            'correlation_id' => $this->correlationId,
            'event_class' => static::class,
        ];
    }
}

// app/Events/Orders/OrderCreated.php
namespace App\Events\Orders;

use App\Events\BaseEvent;
use Illuminate\Support\Facades\Log;

class OrderCreated extends BaseEvent
{
    public function __construct(
        public int $orderId,
        public int $userId,
        public float $totalAmount
    ) {
        parent::__construct();
        
        Log::info('OrderCreated Event Fired', array_merge(
            $this->getTraceContext(),
            [
                'order_id' => $this->orderId,
                'user_id' => $this->userId,
                'total_amount' => $this->totalAmount,
            ]
        ));
    }
}

// app/Events/Orders/OrderCompleted.php
namespace App\Events\Orders;

use App\Events\BaseEvent;
use Illuminate\Support\Facades\Log;

class OrderCompleted extends BaseEvent
{
    public function __construct(
        public int $orderId,
        public int $buyerId,
        public int $sellerId,
        public float $total
    ) {
        parent::__construct();
        
        Log::info('OrderCompleted Event', array_merge(
            $this->getTraceContext(),
            [
                'order_id' => $this->orderId,
                'buyer_id' => $this->buyerId,
                'seller_id' => $this->sellerId,
                'total' => $this->total,
            ]
        ));
    }
}

// app/Events/PaymentSucceeded.php
namespace App\Events;

use Illuminate\Support\Facades\Log;

class PaymentSucceeded extends BaseEvent
{
    public function __construct(
        public int $orderId,
        public string $transactionId,
        public float $paidAmount
    ) {
        parent::__construct();
        
        Log::info('PaymentSucceeded Event', array_merge(
            $this->getTraceContext(),
            [
                'order_id' => $this->orderId,
                'transaction_id' => $this->transactionId,
                'paid_amount' => $this->paidAmount,
            ]
        ));
    }
}

// app/Events/ShipmentCreated.php
namespace App\Events;

use Illuminate\Support\Facades\Log;

class ShipmentCreated extends BaseEvent
{
    public function __construct(
        public int $shipmentId,
        public int $orderId,
        public string $carrier,
        public ?string $trackingNo = null
    ) {
        parent::__construct();
        
        Log::info('ShipmentCreated Event', array_merge(
            $this->getTraceContext(),
            [
                'shipment_id' => $this->shipmentId,
                'order_id' => $this->orderId,
                'carrier' => $this->carrier,
                'tracking_no' => $this->trackingNo,
            ]
        ));
    }
}