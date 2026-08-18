<?php

namespace App\Http\Middleware;

use App\Support\SecurityLog;
use App\Support\UntrustedInput;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class RejectMaliciousInput
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($violation = UntrustedInput::violation($request->all())) {
            SecurityLog::warning('hostile_input_rejected', $request, ['classification' => $violation]);

            throw ValidationException::withMessages([
                'request' => 'A kérés tiltott vagy hibásan kódolt adatot tartalmaz.',
            ]);
        }

        return $next($request);
    }
}
