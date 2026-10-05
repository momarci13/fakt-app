<?php

namespace App\Support;

use App\Models\Event;
use Illuminate\Support\Str;

/**
 * Rotating QR code for self check-in.
 *
 * code(e, w) = first 10 hex chars of HMAC-SHA256(secret_e, "e|w"), with
 * w = ⌊t / 30 s⌋. The organiser's screen shows the current code; a code is
 * accepted in its own window and the previous one (30–60 s of validity), and
 * only from 30 minutes before the start to 30 minutes after the end. A photo
 * of the code forwarded to someone who isn't there expires within a minute.
 */
final class EventCheckIn
{
    public const WINDOW_SECONDS = 30;

    public const GRACE_MINUTES = 30;

    public static function secret(Event $event): string
    {
        if (! $event->checkin_secret) {
            $event->forceFill(['checkin_secret' => Str::random(48)])->save();
        }

        return (string) $event->checkin_secret;
    }

    public static function code(Event $event, ?int $window = null): string
    {
        $window ??= intdiv(now()->getTimestamp(), self::WINDOW_SECONDS);

        return substr(hash_hmac('sha256', $event->id.'|'.$window, self::secret($event)), 0, 10);
    }

    public static function isValid(Event $event, string $code): bool
    {
        if (! $event->checkin_secret) {
            return false;
        }

        $window = intdiv(now()->getTimestamp(), self::WINDOW_SECONDS);

        return hash_equals(self::code($event, $window), $code) || hash_equals(self::code($event, $window - 1), $code);
    }

    public static function isOpen(Event $event): bool
    {
        return $event->status !== 'cancelled'
            && now()->between($event->starts_at->subMinutes(self::GRACE_MINUTES), $event->ends_at->addMinutes(self::GRACE_MINUTES));
    }
}
