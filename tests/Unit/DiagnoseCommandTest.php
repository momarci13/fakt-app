<?php

namespace Tests\Unit;

use App\Console\Commands\Diagnose;
use App\Console\Commands\DiagnoseExceptionHandler;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Tests\TestCase;
use Throwable;

class DiagnoseCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Diagnose::$captured = [];

        parent::tearDown();
    }

    public function test_the_diagnose_command_is_registered(): void
    {
        $this->assertArrayHasKey('fakt:diagnose', Artisan::all());
    }

    public function test_the_capturing_handler_records_every_exception_and_delegates(): void
    {
        Diagnose::$captured = [];

        $inner = new class implements ExceptionHandler
        {
            /** @var array<int, Throwable> */
            public array $reported = [];

            public function report(Throwable $e)
            {
                $this->reported[] = $e;
            }

            public function shouldReport(Throwable $e)
            {
                // The real handler skips HTTP exceptions; the diagnostic must not.
                return false;
            }

            public function render($request, Throwable $e)
            {
                return response('inner', 500);
            }

            public function renderForConsole($output, Throwable $e) {}
        };

        $handler = new DiagnoseExceptionHandler($inner);
        $exception = new RuntimeException('boom');

        $handler->report($exception);

        $this->assertSame([$exception], Diagnose::$captured);
        $this->assertSame([$exception], $inner->reported);
        $this->assertTrue($handler->shouldReport($exception));
    }

    public function test_the_capturing_handler_survives_a_broken_reporter(): void
    {
        Diagnose::$captured = [];

        $inner = new class implements ExceptionHandler
        {
            public function report(Throwable $e)
            {
                throw new RuntimeException('logging is broken');
            }

            public function shouldReport(Throwable $e)
            {
                return true;
            }

            public function render($request, Throwable $e)
            {
                throw new RuntimeException('rendering is broken');
            }

            public function renderForConsole($output, Throwable $e) {}
        };

        $handler = new DiagnoseExceptionHandler($inner);
        $exception = new RuntimeException('boom');

        $handler->report($exception);
        $response = $handler->render(null, $exception);

        $this->assertSame([$exception], Diagnose::$captured);
        $this->assertSame(500, $response->getStatusCode());
    }
}
