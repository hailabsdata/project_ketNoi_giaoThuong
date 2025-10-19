<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShipmentCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $orderId,
        public string $carrier,
        public ?string $trackingNo = null
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Đơn #{$this->orderId} — Đã tạo vận đơn")
            ->line("Đơn #{$this->orderId} đã được chuyển cho hãng: {$this->carrier}.");
        if ($this->trackingNo) $mail->line("Mã vận đơn: {$this->trackingNo}");
        return $mail->action('Theo dõi đơn hàng', url("/orders/{$this->orderId}"));
    }

    public function toArray($notifiable): array
    {
        return [
            'order_id'   => $this->orderId,
            'carrier'    => $this->carrier,
            'trackingNo' => $this->trackingNo,
        ];
    }
}
