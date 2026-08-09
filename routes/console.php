<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50')->everyMinute()->withoutOverlapping(2);
Schedule::command('fakt:recurring-tasks')->everyTenMinutes()->withoutOverlapping();
Schedule::command('fakt:due-reminders')->dailyAt('08:00')->withoutOverlapping();
Schedule::command('fakt:retention')->dailyAt('03:20')->withoutOverlapping();
