<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Laravel\Fortify\Features;
use Tests\TestCase;

/**
 * Two-factor authentication was removed from the application on purpose.
 * These tests fail if any part of it is reintroduced by a package update,
 * a regenerated Fortify action file or a copied starter-kit component.
 */
class TwoFactorRemovedTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_fortify_two_factor_feature_is_disabled(): void
    {
        $this->assertFalse(Features::enabled(Features::twoFactorAuthentication()));
        $this->assertFalse(Features::canManageTwoFactorAuthentication());
    }

    public function test_no_two_factor_routes_are_registered(): void
    {
        $names = collect(Route::getRoutes())
            ->map(fn ($route) => (string) $route->getName())
            ->filter()
            ->all();

        foreach ($names as $name) {
            $this->assertStringNotContainsString('two-factor', $name);
        }
    }

    public function test_the_users_table_has_no_two_factor_columns(): void
    {
        foreach (['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at'] as $column) {
            $this->assertFalse(
                Schema::hasColumn('users', $column),
                "A users tábla nem tartalmazhatja a(z) {$column} oszlopot."
            );
        }
    }

    public function test_the_user_model_no_longer_uses_the_two_factor_trait(): void
    {
        $this->assertNotContains(
            'Laravel\Fortify\TwoFactorAuthenticatable',
            array_keys(class_uses_recursive(User::class))
        );
    }

    public function test_the_mfa_leader_middleware_alias_is_gone(): void
    {
        $this->assertArrayNotHasKey('mfa.leader', app('router')->getMiddleware());
    }
}
