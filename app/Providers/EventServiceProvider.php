<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * Hiện tại module Discovery/Interactions (Duy) không đăng ký event nào bắt buộc.
     */
    protected $listen = [
        // Trống: có thể bổ sung UserRegistered hoặc các event khác nếu cần sau này.
    ];

    public function boot(): void
    {
        //
    }
}
