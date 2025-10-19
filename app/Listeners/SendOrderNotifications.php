<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Events\PaymentSucceeded;
use App\Events\ShipmentCreated;
use App\Models\User; // tuỳ bạn, dùng model nào để tìm buyer/seller
use App\Notifications\OrderStatusChanged;
use App\Notifications\ShipmentCreatedNotification;
use App\Services\Notification\NotificationBridge;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderNotifications implements ShouldQueue
{
    public function __construct(private NotificationBridge $bridge) {}

    public function handle($event): void
    {
        // Gắn switch theo loại sự kiện nghiệp vụ
        switch (true) {
            case $event instanceof PaymentSucceeded:
                $buyer = User::find(/* buyer id từ orderId */); // TODO: lấy từ DB
                if ($buyer) {
                    $this->bridge->to(
                        $buyer,
                        new OrderStatusChanged($event->orderId, 'paid', "Mã GD: {$event->transactionId}")
                    );
                }
                break;

            case $event instanceof ShipmentCreated:
                $buyer = User::find(/* buyer id từ orderId */);
                if ($buyer) {
                    $this->bridge->to(
                        $buyer,
                        new ShipmentCreatedNotification($event->orderId, $event->carrier, $event->trackingNo)
                    );
                }
                break;

            case $event instanceof OrderCreated:
                // Tuỳ nhu cầu: có thể thông báo "đã tạo đơn" cho seller/buyer
                break;
        }
    }
}
