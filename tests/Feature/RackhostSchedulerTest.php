<?php

namespace Tests\Feature;

use App\Notifications\FaktNotification;
use App\Queue\SignalSafeWorker;
use App\Models\User;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Rackhost disables proc_open() and pcntl_signal(). These tests pin the two
 * workarounds: scheduled tasks never spawn a child process, and the queue
 * worker never relies on signal handlers that may not exist.
 */
class RackhostSchedulerTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_scheduled_task_runs_in_process(): void
    {
        $events = app(Schedule::class)->events();

        $this->assertNotEmpty($events);
        foreach ($events as $event) {
            // A command event would be started through proc_open().
            $this->assertInstanceOf(CallbackEvent::class, $event, (string) $event->description);
        }

        $this->assertEqualsCanonicalizing(
            ['queue:work', 'fakt:recurring-tasks', 'fakt:due-reminders', 'fakt:retention'],
            collect($events)->pluck('description')->all(),
        );
    }

    public function test_the_queue_worker_tolerates_disabled_signal_functions(): void
    {
        $this->assertInstanceOf(SignalSafeWorker::class, app('queue.worker'));
    }

    public function test_the_scheduled_queue_task_delivers_queued_notifications(): void
    {
        config(['queue.default' => 'database', 'mail.default' => 'array']);
        $user = User::factory()->create(['approval_status' => 'approved']);
        $user->notify(new FaktNotification('Teszt', 'Új regisztrációs kérelem'));
        $this->assertGreaterThan(0, DB::table('jobs')->count());

        $event = collect(app(Schedule::class)->events())->firstWhere('description', 'queue:work');
        $event->run($this->app);

        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(0, DB::table('failed_jobs')->count());
        $this->assertSame(1, $user->notifications()->count());
    }
}
