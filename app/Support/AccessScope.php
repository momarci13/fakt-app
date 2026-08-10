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

        $professionalUnitIds = OrgUnit::query()
            ->whereIn('id', $user->managedOrgUnitIds())
            ->where(fn ($q) => $q->where('slug', 'like', '%szakmaisag%')->orWhere('name', 'like', '%Szakmaiság%'))
            ->pluck('id');

        return $professionalUnitIds->isNotEmpty();
    }
}
