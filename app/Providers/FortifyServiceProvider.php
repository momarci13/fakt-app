<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use App\Support\SecurityLog;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureAuthentication();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    private function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request): ?User {
            $email = Str::lower(trim((string) $request->input('email')));
            $user = User::query()->where('email', $email)->first();
            $password = (string) $request->input('password');
            $dummyHash = '$2y$12$amBo4gApb/4SywyYYGuqPedCM633XlObud78GUCIK6MdFBAEJEeIu';
            $passwordValid = Hash::check($password, $user?->password ?? $dummyHash);

            if (! $user || ! $passwordValid || ! $user->isApproved()) {
                SecurityLog::warning('login_denied', $request, [
                    'identity_fingerprint' => SecurityLog::fingerprint($email),
                ]);
                return null;
            }

            // Persistent login cookies are intentionally disabled for this internal system.
            $request->merge(['remember' => false]);

            return $user;
        });
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/Login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'passwordRules' => 'minlength:15 maxlength:128',
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/Register', [
            'passwordRules' => 'minlength:15 maxlength:128',
        ]));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/TwoFactorChallenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/ConfirmPassword'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $identity = mb_strtolower(trim((string) $request->input(Fortify::username())));
            $throttleKey = hash_hmac('sha256', $identity.'|'.$request->ip(), (string) config('app.key'));

            return [
                Limit::perMinute(5)->by($throttleKey),
                Limit::perMinute(30)->by('login-ip:'.SecurityLog::fingerprint((string) $request->ip())),
            ];
        });

        RateLimiter::for('mutations', fn (Request $request) => Limit::perMinute(60)
            ->by('mutation:'.($request->user()?->id ?? SecurityLog::fingerprint((string) $request->ip()))));

        RateLimiter::for('sensitive', fn (Request $request) => Limit::perMinute(10)
            ->by('sensitive:'.($request->user()?->id ?? SecurityLog::fingerprint((string) $request->ip()))));

        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinutes(10, 5)
            ->by('upload:'.($request->user()?->id ?? SecurityLog::fingerprint((string) $request->ip()))));
    }
}
