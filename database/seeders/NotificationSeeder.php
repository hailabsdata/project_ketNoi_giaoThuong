<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run()
    {
        $users = User::all();

        $notificationTemplates = [
            [
                'type' => 'order',
                'title' => 'Đơn hàng mới',
                'message' => 'Bạn có đơn hàng mới #ORD-20251201-0001',
                'icon' => 'shopping-cart',
                'priority' => 'high',
                'data' => ['order_id' => 1, 'order_number' => 'ORD-20251201-0001', 'amount' => 29990000],
                'action_url' => '/orders/1',
                'action_text' => 'Xem đơn hàng',
            ],
            [
                'type' => 'payment',
                'title' => 'Thanh toán thành công',
                'message' => 'Thanh toán đơn hàng #ORD-20251201-0001 đã thành công',
                'icon' => 'credit-card',
                'priority' => 'normal',
                'data' => ['payment_id' => 1, 'order_id' => 1, 'amount' => 29990000],
                'action_url' => '/payments/1',
                'action_text' => 'Xem thanh toán',
            ],
            [
                'type' => 'review',
                'title' => 'Đánh giá mới',
                'message' => 'Sản phẩm của bạn nhận được đánh giá 5 sao',
                'icon' => 'star',
                'priority' => 'normal',
                'data' => ['review_id' => 1, 'listing_id' => 123, 'rating' => 5],
                'action_url' => '/reviews/1',
                'action_text' => 'Xem đánh giá',
            ],
            [
                'type' => 'message',
                'title' => 'Tin nhắn mới',
                'message' => 'Bạn có tin nhắn mới từ Nguyễn Văn B',
                'icon' => 'message',
                'priority' => 'high',
                'data' => ['message_id' => 1, 'sender_id' => 5, 'sender_name' => 'Nguyễn Văn B'],
                'action_url' => '/messages/1',
                'action_text' => 'Xem tin nhắn',
            ],
            [
                'type' => 'listing',
                'title' => 'Tin đăng được duyệt',
                'message' => 'Tin đăng "iPhone 15 Pro Max" đã được duyệt',
                'icon' => 'check-circle',
                'priority' => 'normal',
                'data' => ['listing_id' => 123, 'title' => 'iPhone 15 Pro Max'],
                'action_url' => '/listings/123',
                'action_text' => 'Xem tin đăng',
            ],
            [
                'type' => 'shop',
                'title' => 'Gian hàng được xác minh',
                'message' => 'Gian hàng của bạn đã được xác minh',
                'icon' => 'store',
                'priority' => 'high',
                'data' => ['shop_id' => 1],
                'action_url' => '/shops/1',
                'action_text' => 'Xem gian hàng',
            ],
            [
                'type' => 'system',
                'title' => 'Cập nhật hệ thống',
                'message' => 'Hệ thống sẽ bảo trì vào 2h sáng ngày 05/12/2025',
                'icon' => 'info',
                'priority' => 'low',
                'data' => ['maintenance_date' => '2025-12-05T02:00:00.000000Z'],
                'action_url' => null,
                'action_text' => null,
            ],
            [
                'type' => 'promotion',
                'title' => 'Quảng cáo được duyệt',
                'message' => 'Chiến dịch quảng cáo của bạn đã được duyệt',
                'icon' => 'megaphone',
                'priority' => 'normal',
                'data' => ['promotion_id' => 1],
                'action_url' => '/promotions/1',
                'action_text' => 'Xem quảng cáo',
            ],
            [
                'type' => 'verification',
                'title' => 'Xác minh danh tính',
                'message' => 'Yêu cầu xác minh danh tính của bạn đã được chấp nhận',
                'icon' => 'shield-check',
                'priority' => 'high',
                'data' => ['verification_id' => 1],
                'action_url' => '/verification',
                'action_text' => 'Xem chi tiết',
            ],
        ];

        foreach ($users as $user) {
            // Tạo 5-10 notifications cho mỗi user
            $count = rand(5, 10);
            
            for ($i = 0; $i < $count; $i++) {
                $template = $notificationTemplates[array_rand($notificationTemplates)];
                $isRead = rand(1, 10) > 3; // 70% đã đọc
                
                $notification = [
                    'user_id' => $user->id,
                    'type' => $template['type'],
                    'title' => $template['title'],
                    'message' => $template['message'],
                    'data' => $template['data'],
                    'action_url' => $template['action_url'],
                    'action_text' => $template['action_text'],
                    'icon' => $template['icon'],
                    'priority' => $template['priority'],
                    'is_read' => $isRead,
                    'created_at' => now()->subDays(rand(0, 30))->subHours(rand(0, 23)),
                ];

                if ($isRead) {
                    $notification['read_at'] = now()->subDays(rand(0, 15));
                }

                Notification::create($notification);
            }
        }

        $this->command->info('Notifications seeded successfully!');
    }
}
