<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isApproved()) {
            $status = $user->approval_status === 'rejected'
                ? 'A regisztrációs kérelmedet elutasították. További információért keresd az Elnököt.'
                : 'A regisztrációd sikeres. A belépéshez még az Elnök jóváhagyása szükséges.';

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', $status);
        }

        return $next($request);
    }
}
