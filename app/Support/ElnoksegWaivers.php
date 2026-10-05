<?php

namespace App\Support;

use App\Models\ObligationWaiver;
use App\Models\ObligationWaiverVote;
use App\Models\RoleAssignment;
use App\Models\User;
use App\Notifications\FaktNotification;
use Illuminate\Support\Collection;

/**
 * Waivers of an obligation (e.g. a course's absence limit) decided by the
 * whole Elnökség: the Elnök and every Alelnök.
 *
 * Let V be the active Elnökség of the waiver's semester minus the member it
 * concerns (nobody decides their own case). The waiver is approved iff every
 * v ∈ V approves, and rejected as soon as any v ∈ V rejects. If V is empty
 * nobody can decide it, and it stays pending.
 */
final class ElnoksegWaivers
{
    /** @return Collection<int, int> */
    public static function voterIds(ObligationWaiver $waiver): Collection
    {
        return RoleAssignment::query()
            ->where('semester_id', $waiver->semester_id)
            ->whereIn('role', ['president', 'vice_president'])
            ->whereNull('revoked_at')
            ->whereDate('starts_at', '<=', today())
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', today()))
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn (int $id) => $id === (int) $waiver->user_id)
            ->values();
    }

    public static function canVote(User $user, ObligationWaiver $waiver): bool
    {
        return $waiver->status === 'pending' && self::voterIds($waiver)->contains((int) $user->id);
    }

    public static function vote(ObligationWaiver $waiver, User $voter, string $decision, ?string $note = null): ObligationWaiver
    {
        abort_unless(self::canVote($voter, $waiver), 403);

        ObligationWaiverVote::query()->updateOrCreate(
            ['obligation_waiver_id' => $waiver->id, 'user_id' => $voter->id],
            ['decision' => $decision, 'note' => $note],
        );
        Audit::record($waiver, 'waiver_vote_'.$decision);

        return self::resolve($waiver);
    }

    public static function resolve(ObligationWaiver $waiver): ObligationWaiver
    {
        $voters = self::voterIds($waiver);
        $votes = $waiver->votes()->whereIn('user_id', $voters)->pluck('decision', 'user_id');

        $status = match (true) {
            $votes->contains('reject') => 'rejected',
            $voters->isNotEmpty() && $voters->every(fn (int $id) => ($votes[$id] ?? null) === 'approve') => 'approved',
            default => 'pending',
        };

        if ($status !== 'pending') {
            $waiver->update(['status' => $status, 'decided_at' => now()]);
            Audit::record($waiver, 'waiver_'.$status);
            $waiver->user?->notify(new FaktNotification(
                $status === 'approved' ? 'Felmentés megadva' : 'Felmentési kérelem elutasítva',
                $status === 'approved' ? 'Az Elnökség egyhangúlag megadta a felmentést.' : 'Az Elnökség nem adta meg a felmentést.',
                '/felmentesek'
            ));
        }

        return $waiver;
    }
}
