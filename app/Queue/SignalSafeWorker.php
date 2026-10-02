<?php

namespace App\Queue;

use Illuminate\Queue\Worker;

/**
 * Rackhost loads the pcntl extension but lists pcntl_signal() and friends in
 * disable_functions. Laravel's worker only checks extension_loaded('pcntl'),
 * so `queue:work` would die on its first pcntl_signal() call. This worker
 * runs without signal handlers when those functions are missing: jobs still
 * run, only the per-job timeout alarm and graceful-stop signals are skipped.
 */
class SignalSafeWorker extends Worker
{
    public static function from(Worker $worker): self
    {
        return new self(
            $worker->manager,
            $worker->events,
            $worker->exceptions,
            $worker->isDownForMaintenance,
            $worker->resetScope,
        );
    }

    public static function signalsAvailable(): bool
    {
        return extension_loaded('pcntl')
            && function_exists('pcntl_signal')
            && function_exists('pcntl_async_signals')
            && function_exists('pcntl_alarm');
    }

    protected function supportsAsyncSignals()
    {
        return self::signalsAvailable();
    }
}
