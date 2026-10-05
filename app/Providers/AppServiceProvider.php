<?php

namespace App\Providers;

use App\Models\OrgUnit;
use App\Models\Project;
use App\Models\RoleAssignment;
use App\Models\Semester;
use App\Models\TeamMembership;
use App\Queue\SignalSafeWorker;
use App\Support\RequestMemo;
use Carbon\CarbonImmutable;
use Illuminate\Queue\Worker;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Rackhost disables pcntl_signal(); see SignalSafeWorker.
        $this->app->extend('queue.worker', fn (Worker $worker) => SignalSafeWorker::from($worker));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->flushMemoOnScopeChanges();
    }

    /**
     * RequestMemo caches the active semester and each user's roles and scope
     * for one request. Any write to the rows they derive from drops the cache.
     */
    protected function flushMemoOnScopeChanges(): void
    {
        foreach ([Semester::class, RoleAssignment::class, OrgUnit::class, TeamMembership::class, Project::class] as $model) {
            $model::saved(fn () => RequestMemo::flush());
            $model::deleted(fn () => RequestMemo::flush());
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(15)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
