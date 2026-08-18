<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePrivilegedMfa
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $user = $request->user();

        if (config('security.require_privileged_mfa')
            && $user?->isLeader()
            && ! $user->hasEnabledTwoFactorAuthentication()) {
            return redirect()->route('security.edit')->with(
                'error',
                'Vezetői jogosultsághoz kötelező a kétlépcsős azonosítás beállítása.'
            );
        }

        return $next($request);
    }
}
