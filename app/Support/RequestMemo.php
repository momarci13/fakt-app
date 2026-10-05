<?php

namespace App\Support;

use Closure;

/**
 * Per-request memoisation for lookups that every page repeats: the active
 * semester, a user's active roles, their managed org units.
 *
 * Without it one page load re-read the active semester up to 22 times and the
 * role assignments up to 26 times. Values live on the current Request object,
 * so each HTTP request (and each test request) starts empty. Writes to the
 * models these values derive from call flush(), see AppServiceProvider.
 */
final class RequestMemo
{
    private const ATTRIBUTE = '_fakt_memo';

    public static function remember(string $key, Closure $callback): mixed
    {
        $attributes = request()->attributes;
        $store = $attributes->get(self::ATTRIBUTE, []);

        if (! array_key_exists($key, $store)) {
            $store[$key] = $callback();
            $attributes->set(self::ATTRIBUTE, $store);
        }

        return $store[$key];
    }

    public static function flush(): void
    {
        request()->attributes->remove(self::ATTRIBUTE);
    }
}
