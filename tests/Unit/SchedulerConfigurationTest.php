<?php

namespace Tests\Unit;

use App\Console\Kernel;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class SchedulerConfigurationTest extends TestCase
{
    public function test_scheduled_commands_keep_mutexes_without_pcntl_signal_handlers(): void
    {
        $schedule = new Schedule;
        $method = new \ReflectionMethod(Kernel::class, 'schedule');
        $method->invoke(app(Kernel::class), $schedule);

        $expected = [
            'queue:work --stop-when-empty --tries=3' => 15,
            'fakt:recurring-tasks' => 30,
            'fakt:due-reminders' => 120,
            'fakt:retention' => 240,
        ];

        foreach ($expected as $command => $expiryMinutes) {
            $event = collect($schedule->events())
                ->first(fn ($event) => str_contains($event->command, $command));

            $this->assertNotNull($event, "Hiányzik az ütemezett parancs: {$command}");
            $this->assertTrue($event->withoutOverlapping);
            $this->assertFalse($event->releaseOnTerminationSignals);
            $this->assertSame($expiryMinutes, $event->expiresAt);
        }
    }
}
