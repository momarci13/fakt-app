<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Which events belong in a user's calendar:
 *
 *   E(u) = organisation-wide events for their audience
 *        ∪ events of their Teams and of the units they manage
 *        ∪ events of their Projekts
 *        ∪ sessions of the courses they are enrolled in (approved or waitlisted).
 *
 * The same predicate decides who may RSVP or be marked present, so "can
 * respond" and "is in my calendar" can't drift apart.
 */
class PersonalCalendar
{
    /** Enrollment states that put a course's dated sessions in the calendar. */
    public const COURSE_CALENDAR_STATUSES = ['approved', 'waitlisted'];

    public static function events(User $user, ?Semester $semester = null): Collection
    {
        $semester ??= Semester::active();
        if (! $semester) {
            return collect();
        }

        return self::query($user, $semester)
            ->with([
                'organizer:id,name',
                'orgUnit:id,name,color',
                'attendances' => fn ($query) => $query->where('user_id', $user->id),
            ])
            ->orderBy('starts_at')
            ->get();
    }

    public static function query(User $user, ?Semester $semester = null): Builder
    {
        $semester ??= Semester::active();
        if (! $semester) {
            return Event::query()->whereRaw('1 = 0');
        }

        return Event::query()->where('semester_id', $semester->id)->where(self::constraint($user, $semester));
    }

    public static function contains(User $user, Event $event): bool
    {
        return self::query($user)->whereKey($event->id)->exists();
    }

    /** @return Collection<int, int> course ids => enrollment status */
    public static function courseStatuses(User $user): Collection
    {
        return RequestMemo::remember("calendar:courses:{$user->id}", fn () => $user->enrollments()
            ->whereIn('status', self::COURSE_CALENDAR_STATUSES)
            ->pluck('status', 'course_offering_id'));
    }

    private static function constraint(User $user, Semester $semester): \Closure
    {
        $teamIds = $user->teamMemberships()->where('semester_id', $semester->id)->pluck('org_unit_id');
        $managedIds = $user->managedOrgUnitIds();
        $projectIds = $user->projects()->where('projects.semester_id', $semester->id)->pluck('projects.id');
        $courseIds = self::courseStatuses($user)->keys();
        $isAlumni = $user->profile?->member_status === 'alumni';

        return function (Builder $query) use ($teamIds, $managedIds, $projectIds, $courseIds, $isAlumni) {
            $query->where('visibility', $isAlumni ? 'alumni' : 'company')
                ->when(! $isAlumni, fn (Builder $q) => $q->orWhere('visibility', 'members'))
                ->when($teamIds->isNotEmpty(), fn (Builder $q) => $q->orWhereIn('org_unit_id', $teamIds))
                ->when($managedIds->isNotEmpty(), fn (Builder $q) => $q->orWhereIn('org_unit_id', $managedIds))
                ->when($projectIds->isNotEmpty(), fn (Builder $q) => $q->orWhereIn('project_id', $projectIds))
                ->when($courseIds->isNotEmpty(), fn (Builder $q) => $q->orWhereIn('course_offering_id', $courseIds));
        };
    }
}
