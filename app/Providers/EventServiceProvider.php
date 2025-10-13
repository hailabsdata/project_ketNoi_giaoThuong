<?php

namespace App\Providers;

use App\Events\OrderCompleted;
use App\Listeners\SendOrderNotifications;
use App\Listeners\UpdateReports;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderCompleted::class => [
            UpdateReports::class,
            SendOrderNotifications::class,
        ],
    ];

    public function boot(): void {}
}
