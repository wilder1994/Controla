<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('subscriptions:process-lifecycle')->dailyAt('02:00');
Schedule::command('data:purge-retention')->monthlyOn(1, '03:00');
Schedule::command('supervision:auto-close-shifts')->everyFiveMinutes();
