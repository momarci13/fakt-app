<?php

namespace App\Support;

use App\Models\OrgUnit;
use App\Models\RoleAssignment;
use App\Models\Semester;
use App\Models\TeamMembership;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Switches the active semester without losing the leadership.
 *
 * Every role, Team membership and org unit belongs to one semester, so a bare
 * switch used to leave nobody recognised as Elnök: Admin returned 403 and the
 * bootstrap command refused to help. Activation now:
 *
 * 1. creates the statutory org units in the target semester;
 * 2. carries every mandate that is still running when the target starts
 *    (Mandate: Elnök/Alelnök to 30 June, Teamvezető to the half-year end),
 *    mapping org units by slug;
 * 3. optionally carries elected KTSZT seats and Team memberships;
 * 4. refuses, rolling everything back, if the target would have no Elnök.
 *
 * In July the outgoing Elnök's mandate has ended, so the next Elnök must be
 * appointed into the new semester first (AdminController::appointNextPresident).
 */
final class SemesterRollover
{
    private const MANDATE_ROLES = ['president', 'vice_president', 'team_leader'];

    /**
     * @param  array{ktszt?: bool, teams?: bool}  $options
     * @return array{roles: int, memberships: int}
     */
    public static function activate(Semester $target, User $actor, array $options = []): array
    {
        $from = Semester::active();

        return DB::transaction(function () use ($target, $from, $actor, $options): array {
            OrgStructure::ensureFor($target);
            $summary = ['roles' => 0, 'memberships' => 0];

            if ($from && $from->isNot($target)) {
                $unitMap = self::unitMap($from, $target);
                $summary['roles'] = self::carryRoles($from, $target, $unitMap, ($options['ktszt'] ?? false) === true);

                if (($options['teams'] ?? false) === true) {
                    $summary['memberships'] = self::carryMemberships($from, $target, $unitMap, $actor);
                }
            }

            // Activating early (e.g. on 20 December for a 1 January start) must
            // not leave a gap: everything appointed for the target starts today.
            RoleAssignment::query()
                ->where('semester_id', $target->id)
                ->whereNull('revoked_at')
                ->whereDate('starts_at', '>', today())
                ->update(['starts_at' => today()->toDateString()]);

            if (! self::hasActivePresident($target)) {
                throw ValidationException::withMessages([
                    'semester' => 'A(z) '.$target->name.' félévnek nincs Elnöke, ezért nem aktiválható. Előbb nevezd ki a következő Elnököt erre a félévre.',
                ]);
            }

            Semester::query()->whereKeyNot($target->id)->update(['is_active' => false]);
            $target->update(['is_active' => true]);
            Audit::record($target, 'activated', null, $summary);

            return $summary;
        });
    }

    public static function hasActivePresident(Semester $semester): bool
    {
        return RoleAssignment::query()
            ->where('semester_id', $semester->id)
            ->where('role', 'president')
            ->whereNull('revoked_at')
            ->whereDate('starts_at', '<=', today())
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', today()))
            ->exists();
    }

    /** @return Collection<int, int> old org unit id => new org unit id */
    private static function unitMap(Semester $from, Semester $target): Collection
    {
        $targetBySlug = OrgUnit::query()->where('semester_id', $target->id)->pluck('id', 'slug');

        return OrgUnit::query()->where('semester_id', $from->id)->get(['id', 'slug'])
            ->mapWithKeys(fn (OrgUnit $unit) => [$unit->id => $targetBySlug[$unit->slug] ?? null])
            ->filter();
    }

    private static function carryRoles(Semester $from, Semester $target, Collection $unitMap, bool $includeKtszt): int
    {
        $roles = $includeKtszt ? [...self::MANDATE_ROLES, Ktszt::ROLE] : self::MANDATE_ROLES;
        $start = self::carriedStart($target);
        $carried = 0;

        $assignments = RoleAssignment::query()
            ->where('semester_id', $from->id)
            ->whereIn('role', $roles)
            ->whereNull('revoked_at')
            ->orderBy('starts_at')
            ->get();

        foreach ($assignments as $assignment) {
            $end = $assignment->role === Ktszt::ROLE
                ? $assignment->ends_at
                : Mandate::effectiveEnd($assignment->role, $assignment->starts_at, $assignment->ends_at, $from->ends_at);

            if ($end !== null && Carbon::parse($end->toDateString())->lt($target->starts_at->copy()->startOfDay())) {
                continue;
            }

            $unitId = $assignment->org_unit_id ? ($unitMap[$assignment->org_unit_id] ?? null) : null;
            if ($assignment->org_unit_id && ! $unitId) {
                continue;
            }

            if (self::seatTaken($target, $assignment, $unitId)) {
                continue;
            }

            RoleAssignment::query()->create([
                'semester_id' => $target->id,
                'org_unit_id' => $unitId,
                'user_id' => $assignment->user_id,
                'appointed_by' => $assignment->appointed_by,
                'role' => $assignment->role,
                'starts_at' => $start,
                'ends_at' => $end?->toDateString(),
                'note' => 'Mandátum átvitele: '.$from->name,
            ]);
            $carried++;
        }

        return $carried;
    }

    /** One Elnök per semester, one Alelnök per portfolio, one Teamvezető per Team, one seat per KTSZT member. */
    private static function seatTaken(Semester $target, RoleAssignment $assignment, ?int $unitId): bool
    {
        $query = RoleAssignment::query()
            ->where('semester_id', $target->id)
            ->where('role', $assignment->role)
            ->whereNull('revoked_at');

        return match ($assignment->role) {
            'president' => $query->exists(),
            'vice_president', 'team_leader' => $query->where('org_unit_id', $unitId)->exists(),
            default => $query->where('user_id', $assignment->user_id)->exists(),
        };
    }

    private static function carryMemberships(Semester $from, Semester $target, Collection $unitMap, User $actor): int
    {
        $carried = 0;
        $existing = TeamMembership::query()->where('semester_id', $target->id)->pluck('user_id')->flip();

        foreach (TeamMembership::query()->where('semester_id', $from->id)->get() as $membership) {
            $unitId = $unitMap[$membership->org_unit_id] ?? null;
            if (! $unitId || $existing->has($membership->user_id)) {
                continue;
            }

            TeamMembership::query()->create([
                'semester_id' => $target->id,
                'org_unit_id' => $unitId,
                'user_id' => $membership->user_id,
                'assigned_by' => $actor->id,
                'starts_at' => self::carriedStart($target),
                'ends_at' => $target->ends_at->toDateString(),
            ]);
            $carried++;
        }

        return $carried;
    }

    private static function carriedStart(Semester $target): string
    {
        /** @var CarbonInterface $start */
        $start = $target->starts_at;

        return $start->isAfter(today()) ? today()->toDateString() : $start->toDateString();
    }
}
