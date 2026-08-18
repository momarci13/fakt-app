<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class SessionSecurity
{
    public static function revokeFor(User $user, ?string $exceptSessionId = null): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        $query = DB::table((string) config('session.table', 'sessions'))->where('user_id', $user->id);
        if ($exceptSessionId) {
            $query->where('id', '!=', $exceptSessionId);
        }
        $query->delete();
    }
}
