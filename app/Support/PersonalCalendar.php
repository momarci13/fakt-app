<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PersonalCalendar
{
    public static function events(User $user, ?Semester $semester = null): Collection
    {
        $semester ??= Semester::active();
        if (! $semester) {
            return collect();
        }

        $teamIds = $user->teamMemberships()->where('semester_id', $semester->id)->pluck('org_unit_id');
        $managedIds = $user->managedOrgUnitIds();
        $projectIds = $user->projects()->where('projects.semester_id', $semester->id)->pluck('projects.id');
        $courseIds = $user->enrollments()->where('status', 'approved')->pluck('course_offering_id');
        $isAlumni = $user->profile?->member_status === 'alumni';

        return Event::query()
            ->with([
                'organizer:id,name',
                'orgUnit:id,name,color',
                'attendances' => fn ($query) => $query->where('user_id', $user->id),
            ])
            ->where('semester_id', $semester->id)
            ->where(function (Builder $query) use ($teamIds, $managedIds, $projectIds, $courseIds, $isAlumni) {
                $query->where('visibility', $isAlumni ? 'alumni' : 'company')
                    ->when(! $isAlumni, fn (Builder $q) => $q->orWhere('visibility', 'members'))
                    ->when($teamIds->isNotEmpty(), fn (Builder $q) => $q->orWhereIn('org_unit_id', $teamIds))
                    ->when($managedIds->isNotEmpty(), fn (Builder $q) => $q->orWhereIn('org_unit_id', $managedIds))
                    ->when($projectIds->isNotEmpty(), fn (Builder $q) => $q->orWhereIn('project_id', $projectIds))
                    ->when($courseIds->isNotEmpty(), fn (Builder $q) => $q->orWhereIn('course_offering_id', $courseIds));
            })
            ->orderBy('starts_at')
            ->get();
    }
}
