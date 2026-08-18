<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class SecurityLog
{
    /** @param array<string, int|string|null> $context */
    public static function warning(string $event, Request $request, array $context = []): void
    {
        Log::channel('security')->warning($event, array_merge([
            'user_id' => $request->user()?->id,
            'method' => $request->method(),
            'route' => $request->route()?->getName(),
            'path_fingerprint' => self::fingerprint($request->path()),
            'ip_fingerprint' => self::fingerprint((string) $request->ip()),
        ], $context));
    }

    public static function fingerprint(string $value): string
    {
        $key = (string) config('app.key', 'fakt-security');

        return substr(hash_hmac('sha256', $value, $key), 0, 32);
    }
}
