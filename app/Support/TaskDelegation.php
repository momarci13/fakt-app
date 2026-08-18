<?php

namespace App\Support;

use App\Models\Project;
use App\Models\RoleAssignment;
use App\Models\Semester;
use App\Models\TeamMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TaskDelegation
{
    /**
     * @return Collection<int, array{id: int, name: string, delegation_label: string}>
     */
    public function optionsFor(User $actor): Collection
    {
        $semester = Semester::active();
        if (! $semester) {
            return collect();
        }

        $targets = collect([
            $actor->id => ['id' => $actor->id, 'name' => $actor->name, 'delegation_label' => 'Saját feladat'],
        ]);

        if ($actor->isPresident()) {
            $this->addRoleTargets($targets, $semester->id, 'vice_president', 'Alelnök');
            $this->addRoleTargets($targets, $semester->id, 'project_leader', 'Projektvezető');

            return $targets->sortBy('name')->values();
        }

        $portfolioIds = $this->activeRoles($actor, $semester->id)
            ->where('role', 'vice_president')
            ->pluck('org_unit_id')
            ->filter();
        if ($portfolioIds->isNotEmpty()) {
            $leaders = $this->activeRoleQuery($semester->id)
                ->where('role', 'team_leader')
                ->whereHas('orgUnit', fn (Builder $query) => $query->whereIn('parent_id', $portfolioIds))
                ->with('user:id,name,approval_status')
                ->get()
                ->pluck('user')
                ->filter(fn (?User $user) => $user?->isApproved());
            $this->addUsers($targets, $leaders, 'Teamvezető');
        }

        $teamIds = $this->activeRoles($actor, $semester->id)
            ->where('role', 'team_leader')
            ->pluck('org_unit_id')
            ->filter();
        if ($teamIds->isNotEmpty()) {
            $memberIds = TeamMembership::query()
                ->where('semester_id', $semester->id)
                ->whereIn('org_unit_id', $teamIds)
                ->whereDate('starts_at', '<=', today())
                ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', today()))
                ->pluck('user_id');
            $this->addUsers($targets, $this->approvedUsers($memberIds), 'Teamtag');
        }

        $projectIds = Project::query()
            ->where('semester_id', $semester->id)
            ->where('lead_user_id', $actor->id)
            ->where('status', 'active')
            ->pluck('id');
        if ($projectIds->isNotEmpty()) {
            $memberIds = User::query()
                ->whereHas('projects', fn (Builder $query) => $query->whereIn('projects.id', $projectIds))
                ->pluck('id');
            $this->addUsers($targets, $this->approvedUsers($memberIds), 'Projekttag');
        }

        return $targets->sortBy('name')->values();
    }

    /** @return Collection<int, int> */
    public function assignableIdsFor(User $actor): Collection
    {
        return $this->optionsFor($actor)->pluck('id');
    }

    /** @return array{role: string, guidance: string, target_count: int} */
    public function summaryFor(User $actor): array
    {
        $roles = $actor->activeRoleNames();
        $role = match (true) {
            $actor->isPresident() => 'Elnök',
            $roles->contains('vice_president') => 'Alelnök',
            $roles->contains('team_leader') => 'Teamvezető',
            $roles->contains('project_leader') => 'Projektvezető',
            default => 'Tag',
        };
        $guidance = match ($role) {
            'Elnök' => 'Alelnököknek és Projektvezetőknek delegálhatsz.',
            'Alelnök' => 'A portfóliód Teamvezetőinek delegálhatsz.',
            'Teamvezető' => 'A saját Teamed tagjainak delegálhatsz.',
            'Projektvezető' => 'A saját projektjeid tagjainak delegálhatsz.',
            default => 'Saját feladatot hozhatsz létre.',
        };

        return [
            'role' => $role,
            'guidance' => $guidance,
            'target_count' => max(0, $this->optionsFor($actor)->count() - 1),
        ];
    }

    private function activeRoles(User $user, int $semesterId): Collection
    {
        return $this->activeRoleQuery($semesterId)->where('user_id', $user->id)->get();
    }

    private function activeRoleQuery(int $semesterId): Builder
    {
        return RoleAssignment::query()
            ->where('semester_id', $semesterId)
            ->whereNull('revoked_at')
            ->whereDate('starts_at', '<=', today())
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', today()));
    }

    private function addRoleTargets(Collection $targets, int $semesterId, string $role, string $label): void
    {
        $users = $this->activeRoleQuery($semesterId)
            ->where('role', $role)
            ->with('user:id,name,approval_status')
            ->get()
            ->pluck('user')
            ->filter(fn (?User $user) => $user?->isApproved());
        $this->addUsers($targets, $users, $label);
    }

    private function approvedUsers(Collection $ids): Collection
    {
        return User::query()
            ->whereIn('id', $ids)
            ->where('approval_status', 'approved')
            ->get(['id', 'name', 'approval_status']);
    }

    private function addUsers(Collection $targets, Collection $users, string $label): void
    {
        foreach ($users as $user) {
            $targets->put($user->id, [
                'id' => $user->id,
                'name' => $user->name,
                'delegation_label' => $label,
            ]);
        }
    }
}
