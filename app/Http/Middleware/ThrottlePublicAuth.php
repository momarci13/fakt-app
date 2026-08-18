<?php

namespace App\Http\Middleware;

use App\Support\SecurityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottlePublicAuth
{
    /** @var array<string, array{max: int, decay: int}> */
    private const LIMITS = [
        'register' => ['max' => 3, 'decay' => 3600],
        'forgot-password' => ['max' => 3, 'decay' => 3600],
        'reset-password' => ['max' => 5, 'decay' => 3600],
        'email/verification-notification' => ['max' => 6, 'decay' => 3600],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('POST') || ! isset(self::LIMITS[$request->path()])) {
            return $next($request);
        }

        $limit = self::LIMITS[$request->path()];
        $identity = mb_strtolower(trim((string) $request->input('email', 'anonymous')));
        $keys = [
            'public-auth:'.$request->path().':ip:'.SecurityLog::fingerprint((string) $request->ip()),
            'public-auth:'.$request->path().':identity:'.SecurityLog::fingerprint($identity),
        ];

        foreach ($keys as $key) {
            if (RateLimiter::tooManyAttempts($key, $limit['max'])) {
                SecurityLog::warning('public_auth_rate_limited', $request);
                abort(429, 'Túl sok próbálkozás. Próbáld újra később.');
            }
        }

        foreach ($keys as $key) {
            RateLimiter::hit($key, $limit['decay']);
        }

        return $next($request);
    }
}
