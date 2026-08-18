<?php

namespace App\Http\Middleware;

use App\Support\SecurityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceTrustedHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $trustedHost = strtolower((string) config('security.trusted_host'));
        $requestHost = strtolower($request->getHost());

        if (app()->isProduction() && ($trustedHost === '' || ! hash_equals($trustedHost, $requestHost))) {
            SecurityLog::warning('untrusted_host_rejected', $request);
            abort(400, 'Érvénytelen kiszolgálónév.');
        }

        return $next($request);
    }
}
