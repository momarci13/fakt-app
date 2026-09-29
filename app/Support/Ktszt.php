<?php

namespace App\Support;

use App\Models\OrgUnit;
use App\Models\RoleAssignment;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Kurzustervező és -szervező Testület.
 *
 * Seats follow the Testület határozat 2.1-2.2: two ex-officio members and at
 * most three elected ones.
 *
 * The two ex-officio seats are DERIVED from the live role assignments rather
 * than stored. Határozat 4.2 says an elected member who later wins either
 * office converts to an ex-officio seat and their elected mandate ends; if the
 * seats were assigned by hand that rule would break silently whenever an
 * officer changed. Deriving them makes it impossible to get wrong.
 */
final class Ktszt
{
    /** Role name for an elected seat (Határozat 2.2.2). */
    public const ROLE = 'ktszt_member';

    /** Határozat 2.1: at most three elected members. */
    public const MAX_ELECTED = 3;

    /** Roles that carry an ex-officio seat (Határozat 2.2.1). */
    private const EX_OFFICIO_ROLES = ['vice_president', 'team_leader'];

    private static function semesterId(?int $semesterId): ?int
    {
        if ($semesterId !== null) {
            return $semesterId;
        }

        $semester = Semester::active();

        return $semester?->id;
    }

    /** Org units that represent the Szakmaiság portfolio or team. */
    private static function professionalUnitIds(int $semesterId): Collection
    {
        return OrgUnit::query()
            ->where('semester_id', $semesterId)
            ->where(fn ($q) => $q->where('slug', 'like', '%szakmaisag%')->orWhere('name', 'like', '%Szakmaiság%'))
            ->pluck('id');
    }

    /** @return Collection<int, int> */
    public static function exOfficioUserIds(?int $semesterId = null): Collection
    {
        $semesterId = self::semesterId($semesterId);

        if (! $semesterId) {
            return collect();
        }

        $unitIds = self::professionalUnitIds($semesterId);

        if ($unitIds->isEmpty()) {
            return collect();
        }

        return self::activeAssignments($semesterId)
            ->whereIn('role', self::EX_OFFICIO_ROLES)
            ->whereIn('org_unit_id', $unitIds)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    /**
     * Elected seats, oldest mandate first, hard-capped at three.
     *
     * Határozat 4.2: an elected member who has since become an ex-officio
     * member no longer holds an elected seat.
     *
     * @return Collection<int, int>
     */
    public static function electedUserIds(?int $semesterId = null): Collection
    {
        $semesterId = self::semesterId($semesterId);

        if (! $semesterId) {
            return collect();
        }

        $exOfficio = self::exOfficioUserIds($semesterId);

        return self::activeAssignments($semesterId)
            ->where('role', self::ROLE)
            ->sortBy('starts_at')
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn (int $id) => $exOfficio->contains($id))
            ->take(self::MAX_ELECTED)
            ->values();
    }

    /** @return Collection<int, int> */
    public static function memberUserIds(?int $semesterId = null): Collection
    {
        return self::exOfficioUserIds($semesterId)
            ->merge(self::electedUserIds($semesterId))
            ->unique()
            ->values();
    }

    public static function isMember(?User $user, ?int $semesterId = null): bool
    {
        return $user !== null && self::memberUserIds($semesterId)->contains((int) $user->id);
    }

    public static function electedSeatsRemaining(?int $semesterId = null): int
    {
        return max(0, self::MAX_ELECTED - self::electedUserIds($semesterId)->count());
    }

    /**
     * Határozat 3.3: the Szakmaiságért felelős Alelnök chairs the Testület,
     * the Szakmaiság Teamvezető stands in when they are absent.
     */
    public static function chair(?int $semesterId = null): ?User
    {
        $semesterId = self::semesterId($semesterId);

        if (! $semesterId) {
            return null;
        }

        $unitIds = self::professionalUnitIds($semesterId);

        foreach (self::EX_OFFICIO_ROLES as $role) {
            $assignment = self::activeAssignments($semesterId)
                ->where('role', $role)
                ->whereIn('org_unit_id', $unitIds)
                ->sortBy('starts_at')
                ->first();

            if ($assignment) {
                return User::query()->find($assignment->user_id);
            }
        }

        return null;
    }

    /**
     * Határozat 1.2.6: a member takes no part in a decision touching their own
     * submission, their own course completion, or a plagiarism suspicion
     * against them.
     *
     * Applied to everyone, not only to Testület members: nobody rules on their
     * own record. The caller passes every user the record belongs to, so
     * co-authored submissions exclude every co-author.
     *
     * @param  iterable<int|string|null>  $subjectUserIds
     */
    public static function canDecideOn(?User $actor, iterable $subjectUserIds): bool
    {
        if ($actor === null) {
            return false;
        }

        foreach ($subjectUserIds as $id) {
            if ($id !== null && (int) $id === (int) $actor->id) {
                return false;
            }
        }

        return true;
    }

    /**
     * The assignments behind the ex-officio seats, with the user loaded, so
     * the panel can say which office each seat comes from.
     *
     * @return Collection<int, RoleAssignment>
     */
    public static function exOfficioAssignments(?int $semesterId = null): Collection
    {
        $semesterId = self::semesterId($semesterId);

        if (! $semesterId) {
            return collect();
        }

        $unitIds = self::professionalUnitIds($semesterId);

        return self::activeAssignments($semesterId)
            ->whereIn('role', self::EX_OFFICIO_ROLES)
            ->whereIn('org_unit_id', $unitIds)
            ->unique('user_id')
            ->load('user:id,name,email')
            ->values();
    }

    /**
     * The assignments behind the elected seats, matching electedUserIds()
     * exactly, so the revoke control always targets a seat that is really held.
     *
     * @return Collection<int, RoleAssignment>
     */
    public static function electedAssignments(?int $semesterId = null): Collection
    {
        $semesterId = self::semesterId($semesterId);

        if (! $semesterId) {
            return collect();
        }

        $elected = self::electedUserIds($semesterId);

        return self::activeAssignments($semesterId)
            ->where('role', self::ROLE)
            ->whereIn('user_id', $elected)
            ->sortBy('starts_at')
            ->unique('user_id')
            ->load('user:id,name,email')
            ->values();
    }

    /** @return Collection<int, RoleAssignment> */
    private static function activeAssignments(int $semesterId): Collection
    {
        return RoleAssignment::query()
            ->where('semester_id', $semesterId)
            ->whereNull('revoked_at')
            ->whereDate('starts_at', '<=', today())
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', today()))
            ->get();
    }
}
