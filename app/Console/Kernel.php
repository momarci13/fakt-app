<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;

class Kernel extends ConsoleKernel
{
    /**
     * Rackhost disables proc_open() and pcntl_signal(). A `$schedule->command()`
     * event starts a child process through proc_open(), so every task failed
     * with "The Process class relies on proc_open". These tasks therefore run
     * inside the `schedule:run` process itself, through Artisan::call().
     *
     * Each task keeps its mutex (withoutOverlapping), but the second argument
     * stops Laravel from installing pcntl signal handlers for it.
     */
    protected function schedule(Schedule $schedule)
    {
        $this->inProcess($schedule, 'queue:work', [
            '--stop-when-empty' => true,
            '--tries' => 3,
            '--max-time' => 50,
            '--sleep' => 1,
        ])->everyMinute()->withoutOverlapping(15, false);

        $this->inProcess($schedule, 'fakt:recurring-tasks')->everyTenMinutes()->withoutOverlapping(30, false);
        $this->inProcess($schedule, 'fakt:due-reminders')->dailyAt('08:00')->withoutOverlapping(120, false);
        $this->inProcess($schedule, 'fakt:retention')->dailyAt('03:20')->withoutOverlapping(240, false);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function inProcess(Schedule $schedule, string $command, array $parameters = []): \Illuminate\Console\Scheduling\CallbackEvent
    {
        return $schedule
            ->call(function () use ($command, $parameters): int {
                $exitCode = Artisan::call($command, $parameters);

                if ($exitCode !== 0) {
                    throw new \RuntimeException("{$command} exited with code {$exitCode}: ".trim(Artisan::output()));
                }

                return $exitCode;
            })
            ->name($command);
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
