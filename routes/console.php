<?php

use App\Jobs\CleanupDraftTickets;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('email-queue:flush')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('reservations:cancel-expired')
    ->hourly()
    ->withoutOverlapping();

Schedule::job(new CleanupDraftTickets)
    ->hourly()
    ->withoutOverlapping();

Schedule::command('flights:recompute-prices')
    ->daily()
    ->withoutOverlapping();
