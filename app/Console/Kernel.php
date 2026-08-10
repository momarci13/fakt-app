<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('queue:work --stop-when-empty --tries=3')->everyMinute()->withoutOverlapping();
        $schedule->command('fakt:recurring-tasks')->everyTenMinutes()->withoutOverlapping();
        $schedule->command('fakt:due-reminders')->dailyAt('08:00')->withoutOverlapping();
        $schedule->command('fakt:retention')->dailyAt('03:20')->withoutOverlapping();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
