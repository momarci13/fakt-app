<?php

namespace App\Support;

use App\Models\ObligationRule;
use App\Models\ProgressRecord;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Support\Collection;

class LifecycleProgress
{
    public static function for(User $user, ?Semester $semester = null): Collection
    {
        $semester ??= Semester::active();
        if (! $semester) {
            return collect();
        }

        $totals = ProgressRecord::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->selectRaw('type, SUM(value) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return ObligationRule::query()
            ->where('semester_id', $semester->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(function (ObligationRule $rule) use ($totals) {
                $current = (float) ($totals[$rule->code] ?? 0);
                $maximum = $rule->kind === 'maximum';
                $complete = $maximum ? $current < $rule->threshold : $current >= $rule->threshold;
                $percent = $maximum
                    ? max(0, min(100, 100 - (($current / max(1, $rule->threshold)) * 100)))
                    : min(100, ($current / max(1, $rule->threshold)) * 100);

                return [
                    'code' => $rule->code,
                    'name' => $rule->name,
                    'description' => $rule->description,
                    'current' => $current,
                    'threshold' => (float) $rule->threshold,
                    'kind' => $rule->kind,
                    'complete' => $complete,
                    'percent' => round($percent),
                ];
            });
    }
}
