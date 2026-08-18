<?php

namespace App\Support;

use App\Models\AuditEntry;
use Illuminate\Database\Eloquent\Model;

class Audit
{
    public static function record(Model $model, string $event, ?array $before = null, ?array $after = null): void
    {
        $ipAddress = app()->runningInConsole() && ! app()->runningUnitTests()
            ? null
            : request()->ip();

        AuditEntry::query()->create([
            'actor_id' => auth()->id(),
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'event' => $event,
            'before' => self::redact($before),
            'after' => self::redact($after ?? $model->toArray()),
            'ip_address' => $ipAddress === null ? null : 'ip#'.substr(hash_hmac('sha256', $ipAddress, (string) config('app.key')), 0, 40),
            'created_at' => now(),
        ]);
    }

    /** @param array<string, mixed>|null $values */
    private static function redact(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $sensitiveFragments = [
            'password', 'token', 'secret', 'recovery', 'calendar_token',
            'registration_note', 'rejection_reason', 'reason', 'evidence_path',
            'path', 'body', 'minutes',
        ];

        foreach ($values as $key => $value) {
            $normalizedKey = strtolower((string) $key);
            if (collect($sensitiveFragments)->contains(fn (string $fragment) => str_contains($normalizedKey, $fragment))) {
                $values[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($value)) {
                $values[$key] = self::redact($value);
            }
        }

        return $values;
    }
}
