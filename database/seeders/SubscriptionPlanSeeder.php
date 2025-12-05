<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubscriptionPlan;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Gói miễn phí cho người mới bắt đầu. Phù hợp để trải nghiệm nền tảng.',
                'price' => 0,
                'currency' => 'VND',
                'duration_days' => 30,
                'features' => [
                    'max_listings' => 10,
                    'max_images_per_listing' => 5,
                    'featured_listings' => 0,
                    'priority_support' => false,
                    'analytics' => false,
                    'custom_domain' => false,
                ],
                'benefits' => [
                    'Đăng tối đa 10 tin',
                    'Tối đa 5 ảnh/tin',
                    'Hiển thị thông thường',
                    'Hỗ trợ cơ bản',
                ],
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'description' => 'Gói cơ bản cho seller nhỏ. Bao gồm các tính năng thiết yếu để bắt đầu kinh doanh.',
                'price' => 500000,
                'currency' => 'VND',
                'duration_days' => 30,
                'features' => [
                    'max_listings' => 50,
                    'max_images_per_listing' => 10,
                    'featured_listings' => 5,
                    'priority_support' => false,
                    'analytics' => true,
                    'custom_domain' => false,
                ],
                'benefits' => [
                    'Đăng tối đa 50 tin',
                    'Tối đa 10 ảnh/tin',
                    '5 tin nổi bật miễn phí',
                    'Thống kê cơ bản',
                    'Hỗ trợ ưu tiên',
                ],
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 2,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Gói chuyên nghiệp cho seller lớn. Bao gồm tất cả tính năng cần thiết để phát triển kinh doanh.',
                'price' => 1000000,
                'currency' => 'VND',
                'duration_days' => 30,
                'features' => [
                    'max_listings' => 200,
                    'max_images_per_listing' => 20,
                    'featured_listings' => 20,
                    'priority_support' => true,
                    'analytics' => true,
                    'custom_domain' => false,
                    'promotion_discount' => 10,
                ],
                'benefits' => [
                    'Đăng tối đa 200 tin',
                    'Tối đa 20 ảnh/tin',
                    '20 tin nổi bật miễn phí',
                    'Hỗ trợ ưu tiên 24/7',
                    'Thống kê chi tiết',
                    'Giảm 10% chi phí quảng cáo',
                ],
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Gói doanh nghiệp không giới hạn. Dành cho các doanh nghiệp lớn với nhu cầu cao.',
                'price' => 2000000,
                'currency' => 'VND',
                'duration_days' => 30,
                'features' => [
                    'max_listings' => -1,
                    'max_images_per_listing' => 50,
                    'featured_listings' => -1,
                    'priority_support' => true,
                    'analytics' => true,
                    'custom_domain' => true,
                    'api_access' => true,
                    'dedicated_account_manager' => true,
                ],
                'benefits' => [
                    'Đăng tin không giới hạn',
                    'Tối đa 50 ảnh/tin',
                    'Tin nổi bật không giới hạn',
                    'Hỗ trợ 24/7 ưu tiên cao',
                    'Thống kê nâng cao',
                    'Tên miền riêng',
                    'API tích hợp',
                    'Quản lý tài khoản riêng',
                ],
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 4,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }

        $this->command->info('✅ Created/Updated ' . count($plans) . ' subscription plans');
    }
}
