<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Bookmark;
use App\Models\Listing;
use App\Models\User;

class BookmarkSeeder extends Seeder
{
    public function run(): void
    {
        $buyers = User::where('role', 'buyer')->get();
        $listings = Listing::take(5)->get();

        if ($buyers->count() === 0 || $listings->count() === 0) {
            $this->command->warn('  No buyers or listings found. Skipping bookmark seeder.');
            return;
        }

        $count = 0;
        foreach ($buyers as $buyer) {
            // Mỗi buyer bookmark 2-3 listings
            $bookmarkCount = rand(2, 3);
            $randomListings = $listings->random(min($bookmarkCount, $listings->count()));

            foreach ($randomListings as $listing) {
                Bookmark::firstOrCreate([
                    'user_id' => $buyer->id,
                    'listing_id' => $listing->id,
                ]);
                $count++;
            }
        }

        $this->command->info(' Created ' . $count . ' bookmarks');
    }
}
