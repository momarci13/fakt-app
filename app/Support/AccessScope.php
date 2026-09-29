<?php

namespace App\Support;

use App\Models\OrgUnit;
use App\Models\Project;
use App\Models\User;

class AccessScope
{
    public static function managesUnit(User $user, ?int $orgUnitId): bool
    {
        return $user->isPresident() || ($orgUnitId && $user->managedOrgUnitIds()->contains($orgUnitId));
    }

    public static function managesProject(User $user, ?int $projectId): bool
    {
        if ($user->isPresident()) {
            return true;
        }
        if (! $projectId) {
            return false;
        }

        $project = Project::query()->find($projectId);

        return $project && ((int) $project->lead_user_id === (int) $user->id || self::managesUnit($user, $project->org_unit_id));
    }

    public static function managesCourses(User $user): bool
    {
        if ($user->isPresident()) {
            return true;
        }

        // KTSzT határozat 1.2: course planning, enrollment, placement and
        // completion decisions belong to the Testület.
        if (Ktszt::isMember($user)) {
            return true;
        }

        $professionalUnitIds = OrgUnit::query()
            ->whereIn('id', $user->managedOrgUnitIds())
            ->where(fn ($q) => $q->where('slug', 'like', '%szakmaisag%')->orWhere('name', 'like', '%Szakmaiság%'))
            ->pluck('id');

        return $professionalUnitIds->isNotEmpty();
    }

    /**
     * KTSzT határozat 1.2.6, applied to everyone rather than only to Testület
     * members: nobody decides a record they are the subject of. Pass every user
     * the record belongs to so a co-authored submission excludes each co-author.
     */
    public static function canDecideFor(User $actor, int|string|null ...$subjectUserIds): bool
    {
        return Ktszt::canDecideOn($actor, $subjectUserIds);
    }
}
