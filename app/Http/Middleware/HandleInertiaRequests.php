<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), ['name' => config('app.name'), 'auth' => [
            'user' => ($nullsafeVariable1 = $request->user()) ? $nullsafeVariable1->loadMissing('profile') : null,
            'roles' => fn () => (($nullsafeVariable2 = $request->user()) ? $nullsafeVariable2->activeRoleNames() : null) ?? [],
            'abilities' => fn () => [
                'isPresident' => (($nullsafeVariable3 = $request->user()) ? $nullsafeVariable3->isPresident() : null) ?? false,
                'isLeader' => (($nullsafeVariable4 = $request->user()) ? $nullsafeVariable4->isLeader() : null) ?? false,
            ],
            'notifications' => function () use ($request) {
                if (! $request->user()) {
                    return [];
                }

                return $request->user()->unreadNotifications()->latest()->take(5)->get()->map(function ($notification) {
                    return array_merge(
                        ['id' => $notification->id],
                        (array) $notification->data,
                        ['created_at' => $notification->created_at]
                    );
                });
            },
        ], 'flash' => [
            'success' => fn () => $request->session()->get('success'),
            'error' => fn () => $request->session()->get('error'),
        ], 'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true']);
    }
}
