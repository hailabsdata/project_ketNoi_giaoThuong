<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
// use Illuminate\Notifications\Messages\MailMessage; // nếu muốn gửi email

class OrderCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $orderId,
        public float $amount,
        public string $role // 'buyer' hoặc 'seller'
    ) {}

    public function via($notifiable): array
    {
        // Dùng database để tránh cấu hình SMTP; nếu muốn email, thêm 'mail'
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type'     => 'order_completed',
            'order_id' => $this->orderId,
            'amount'   => $this->amount,
            'role'     => $this->role,
            'message'  => $this->role === 'buyer'
                ? 'Đơn hàng của bạn đã hoàn tất.'
                : 'Bạn đã bán thành công 1 đơn hàng.',
        ];
    }

    //Nếu muốn gửi email, bỏ comment và set MAIL_MAILER=smtp
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Order Completed #' . $this->orderId)
            ->line('Số tiền: ' . number_format($this->amount, 2))
            ->action('Xem đơn', url('/orders/' . $this->orderId));
    }
}
