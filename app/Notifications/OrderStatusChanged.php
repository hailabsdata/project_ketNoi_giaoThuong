<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Thông báo khi trạng thái đơn thay đổi (ví dụ: đã thanh toán).
 * Kênh: mail + database (dev dễ test, sau thêm broadcast/FCM).
 */
class OrderStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $orderId,
        public string $newStatus,
        public ?string $note = null
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Đơn #{$this->orderId} — {$this->newStatus}")
            ->line("Trạng thái đơn #{$this->orderId} đã chuyển sang: {$this->newStatus}.")
            ->when($this->note, fn($m) => $m->line($this->note))
            ->action('Xem đơn hàng', url("/orders/{$this->orderId}"));
    }

    public function toArray($notifiable): array
    {
        return [
            'order_id'  => $this->orderId,
            'newStatus' => $this->newStatus,
            'note'      => $this->note,
        ];
    }
}
