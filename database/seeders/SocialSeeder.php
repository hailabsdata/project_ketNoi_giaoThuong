<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ListingLike;
use App\Models\ListingComment;
use App\Models\Listing;
use App\Models\User;

class SocialSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $listings = Listing::take(10)->get();

        if ($users->count() === 0 || $listings->count() === 0) {
            $this->command->warn('⚠️  No users or listings found. Skipping social seeder.');
            return;
        }

        $likesCount = 0;
        $commentsCount = 0;

        // Create likes
        foreach ($listings as $listing) {
            // Random 3-8 users like each listing
            $likeCount = rand(3, 8);
            $randomUsers = $users->random(min($likeCount, $users->count()));

            foreach ($randomUsers as $user) {
                ListingLike::firstOrCreate([
                    'listing_id' => $listing->id,
                    'user_id' => $user->id,
                ]);
                $likesCount++;
            }
        }

        // Create comments
        $comments = [
            'Sản phẩm này có bảo hành không shop?',
            'Còn hàng không ạ?',
            'Giá này đã bao gồm ship chưa?',
            'Sản phẩm chất lượng tốt, mình đã mua rồi!',
            'Shop giao hàng nhanh không?',
            'Có màu khác không shop?',
            'Cho mình xin thêm ảnh thực tế được không?',
            'Sản phẩm này có giảm giá không?',
            'Mình muốn mua 2 cái, có giảm không?',
            'Shop ơi, inbox mình với!',
        ];

        foreach ($listings as $listing) {
            // Random 2-5 comments per listing
            $commentCount = rand(2, 5);

            for ($i = 0; $i < $commentCount; $i++) {
                $randomUser = $users->random();
                $randomComment = $comments[array_rand($comments)];

                ListingComment::create([
                    'listing_id' => $listing->id,
                    'user_id' => $randomUser->id,
                    'body' => $randomComment,
                    'created_at' => now()->subDays(rand(1, 30)),
                ]);
                $commentsCount++;
            }
        }

        $this->command->info('✅ Created ' . $likesCount . ' likes and ' . $commentsCount . ' comments');
    }
}
