<?php

namespace App\Listeners;

use App\Support\Audit;
use Illuminate\Auth\Events\Login;

class RecordSuccessfulLogin
{
    public function handle(Login $event): void
    {
        $event->user->forceFill(['last_seen_at' => now()])->saveQuietly();
        Audit::record($event->user, 'login_succeeded', null, ['user_id' => $event->user->getKey()]);
    }
}
