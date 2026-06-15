<?php

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

// Dynamic ticket prices are refreshed once a day, but the command itself only
// touches flights whose price is older than pricing.recompute_interval_days,
// so a given flight's price changes at most every few days.
Schedule::command('flights:recompute-prices')
    ->daily()
    ->withoutOverlapping();
