<?php

namespace App\Support;

use App\Models\AuditEntry;
use Illuminate\Database\Eloquent\Model;

class Audit
{
    public static function record(Model $model, string $event, ?array $before = null, ?array $after = null): void
    {
        AuditEntry::query()->create([
            'actor_id' => auth()->id(),
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'event' => $event,
            'before' => $before,
            'after' => $after ?? $model->toArray(),
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'created_at' => now(),
        ]);
    }
}
