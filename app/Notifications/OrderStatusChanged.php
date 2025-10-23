<?php
// app/Notifications/OrderStatusChanged.php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use App\Support\Correlation;

class OrderStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $orderId,
        public string $newStatus,
        public ?string $note = null,
        public ?string $correlationId = null // ← THÊM CORRELATION ID
    ) {
        $this->correlationId = $correlationId ?? Correlation::correlationId();
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        Log::info('Sending OrderStatusChanged Email', [
            'correlation_id' => $this->correlationId,
            'order_id' => $this->orderId,
            'status' => $this->newStatus,
            'recipient' => $notifiable->email,
        ]);

        return (new MailMessage)
            ->subject("Đơn #{$this->orderId} — {$this->newStatus}")
            ->line("Trạng thái đơn #{$this->orderId} đã chuyển sang: {$this->newStatus}.")
            ->when($this->note, fn($m) => $m->line($this->note))
            ->action('Xem đơn hàng', url("/orders/{$this->orderId}"));
    }

    public function toArray($notifiable): array
    {
        return [
            'order_id' => $this->orderId,
            'new_status' => $this->newStatus,
            'note' => $this->note,
            'correlation_id' => $this->correlationId, // ← LƯU VÀO DATABASE
        ];
    }
}

// app/Notifications/ShipmentCreatedNotification.php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use App\Support\Correlation;

class ShipmentCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $orderId,
        public string $carrier,
        public ?string $trackingNo = null,
        public ?string $correlationId = null
    ) {
        $this->correlationId = $correlationId ?? Correlation::correlationId();
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        Log::info('Sending ShipmentCreated Email', [
            'correlation_id' => $this->correlationId,
            'order_id' => $this->orderId,
            'carrier' => $this->carrier,
            'tracking_no' => $this->trackingNo,
            'recipient' => $notifiable->email,
        ]);

        $mail = (new MailMessage)
            ->subject("Đơn #{$this->orderId} — Đã tạo vận đơn")
            ->line("Đơn #{$this->orderId} đã được chuyển cho hãng: {$this->carrier}.");
            
        if ($this->trackingNo) {
            $mail->line("Mã vận đơn: {$this->trackingNo}");
        }
        
        return $mail->action('Theo dõi đơn hàng', url("/orders/{$this->orderId}"));
    }

    public function toArray($notifiable): array
    {
        return [
            'order_id' => $this->orderId,
            'carrier' => $this->carrier,
            'tracking_no' => $this->trackingNo,
            'correlation_id' => $this->correlationId,
        ];
    }
}

// app/Notifications/OrderCompletedNotification.php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use App\Support\Correlation;

class OrderCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $orderId,
        public float $amount,
        public string $role, // 'buyer' hoặc 'seller'
        public ?string $correlationId = null
    ) {
        $this->correlationId = $correlationId ?? Correlation::correlationId();
    }

    public function via($notifiable): array
    {
        return ['database']; // Chỉ database để test nhanh
    }

    public function toArray($notifiable): array
    {
        Log::info('OrderCompleted Notification Created', [
            'correlation_id' => $this->correlationId,
            'order_id' => $this->orderId,
            'role' => $this->role,
            'recipient_id' => $notifiable->id,
        ]);
        
        return [
            'type' => 'order_completed',
            'order_id' => $this->orderId,
            'amount' => $this->amount,
            'role' => $this->role,
            'message' => $this->role === 'buyer'
                ? 'Đơn hàng của bạn đã hoàn tất.'
                : 'Bạn đã bán thành công 1 đơn hàng.',
            'correlation_id' => $this->correlationId,
        ];
    }
}