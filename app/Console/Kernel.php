<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // Rackhost loads the pcntl extension but disables pcntl_signal(). Keep the
        // mutexes, but do not ask Laravel to install termination-signal handlers.
        $schedule->command('queue:work --stop-when-empty --tries=3')->everyMinute()->withoutOverlapping(15, false);
        $schedule->command('fakt:recurring-tasks')->everyTenMinutes()->withoutOverlapping(30, false);
        $schedule->command('fakt:due-reminders')->dailyAt('08:00')->withoutOverlapping(120, false);
        $schedule->command('fakt:retention')->dailyAt('03:20')->withoutOverlapping(240, false);
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
