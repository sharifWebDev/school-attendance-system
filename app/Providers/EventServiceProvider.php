<?php
// app/Providers/EventServiceProvider.php

namespace App\Providers;

use App\Events\AttendanceRecorded;
use App\Listeners\SendAttendanceNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        AttendanceRecorded::class => [
            SendAttendanceNotification::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
