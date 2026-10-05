<?php

namespace Tests\Unit;

use App\Console\Kernel;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class SchedulerConfigurationTest extends TestCase
{
    public function test_scheduled_tasks_keep_mutexes_without_pcntl_signal_handlers(): void
    {
        $schedule = new Schedule;
        $method = new \ReflectionMethod(Kernel::class, 'schedule');
        $method->invoke(app(Kernel::class), $schedule);

        $expected = [
            'queue:work' => ['* * * * *', 15],
            'fakt:recurring-tasks' => ['*/10 * * * *', 30],
            'fakt:due-reminders' => ['0 8 * * *', 120],
            'fakt:retention' => ['20 3 * * *', 240],
            'fakt:daily-digest' => ['30 7 * * *', 60],
        ];

        // Plus the heartbeat shown on Admin → Rendszerállapot.
        $this->assertCount(count($expected) + 1, $schedule->events());
        $heartbeat = collect($schedule->events())->firstWhere('description', 'fakt:scheduler-heartbeat');
        $this->assertInstanceOf(CallbackEvent::class, $heartbeat);
        $this->assertSame('* * * * *', $heartbeat->expression);

        foreach ($expected as $name => [$cron, $expiryMinutes]) {
            $event = collect($schedule->events())->firstWhere('description', $name);

            $this->assertNotNull($event, "Hiányzik az ütemezett feladat: {$name}");
            // In-process: Rackhost disables proc_open(), which command events need.
            $this->assertInstanceOf(CallbackEvent::class, $event);
            $this->assertSame($cron, $event->expression);
            $this->assertTrue($event->withoutOverlapping);
            $this->assertFalse($event->releaseOnTerminationSignals);
            $this->assertSame($expiryMinutes, $event->expiresAt);
        }
    }
}
