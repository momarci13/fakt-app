<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use App\Support\Audit;
use App\Support\SessionSecurity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    /**
     * Show the user's security settings page.
     *
     * Two-factor authentication was removed from the application. The password
     * policy, session revocation and the audit trail are the remaining controls.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Security', [
            'passwordRules' => 'minlength:15 maxlength:128',
        ]);
    }

    /**
     * Update the user's password and sign every other session out.
     */
    public function update(PasswordUpdateRequest $request): RedirectResponse
    {
        $request->user()->forceFill([
            'password' => Hash::make($request->password),
            'remember_token' => Str::random(60),
        ])->save();

        SessionSecurity::revokeFor($request->user(), $request->session()->getId());
        $request->session()->regenerate();

        Audit::record($request->user(), 'password_changed', null, ['user_id' => $request->user()->id]);

        return back()->with('success', __('Password updated.'));
    }
}
