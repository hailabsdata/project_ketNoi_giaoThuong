<?php

namespace App\Listeners;

use App\Events\OrderCompleted;
use App\Notifications\OrderCompletedNotification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderNotifications implements ShouldQueue
{
    public function handle(OrderCompleted $event): void
    {
        // lấy buyer & seller (giả sử có bảng users)
        $buyer  = User::find($event->buyerId);
        $seller = User::find($event->sellerId);

        // gửi notification qua kênh "database" + "log mailer" (nếu bật toMail)
        if ($buyer)  $buyer->notify(new OrderCompletedNotification($event->orderId, $event->total, 'buyer'));
        if ($seller) $seller->notify(new OrderCompletedNotification($event->orderId, $event->total, 'seller'));
    }
}
