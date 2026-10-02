<?php

namespace App\Http\Controllers;

use App\Models\OrgUnit;
use App\Models\Project;
use App\Models\RoleAssignment;
use App\Models\Semester;
use App\Models\TeamMembership;
use App\Models\User;
use App\Support\AccessScope;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function index(Request $request): Response
    {
        $semester = Semester::active();
        $units = OrgUnit::query()->where('semester_id', ($nullsafeVariable1 = $semester) ? $nullsafeVariable1->id : null)
            ->with(['roles' => fn ($q) => $q->whereNull('revoked_at')->with('user:id,name,email'), 'memberships.user:id,name,email', 'children'])
            ->orderBy('type')->orderBy('name')->get();

        return Inertia::render('Organization/Index', [
            'semester' => $semester,
            'units' => $units,
            'projects' => Project::query()->where('semester_id', ($nullsafeVariable2 = $semester) ? $nullsafeVariable2->id : null)->with(['lead:id,name', 'members:id,name', 'orgUnit:id,name'])->get(),
            'members' => User::query()->where('approval_status', 'approved')->with('profile')->orderBy('name')->get(['id', 'name', 'email']),
            'canAdmin' => $request->user()->isPresident(),
            'managedUnitIds' => $request->user()->managedOrgUnitIds(),
            // Teams whose Teamvezető this user may appoint or revoke.
            'appointableTeamIds' => $units
                ->where('type', 'team')
                ->filter(fn (OrgUnit $team) => AccessScope::appointsTeamLeader($request->user(), $team))
                ->pluck('id')
                ->sort()
                ->values(),
            // The Elnök manages every Projekt; a Projektvezető manages their own.
            'managedProjectIds' => $request->user()->isPresident()
                ? Project::query()->where('semester_id', $semester?->id)->pluck('id')
                : Project::query()->where('semester_id', $semester?->id)->where('lead_user_id', $request->user()->id)->pluck('id'),
        ]);
    }

    public function appoint(Request $request): RedirectResponse
    {
        $semester = Semester::activeOrFail();
        $data = $request->validate([
            'user_id' => ['required', Rule::exists('users', 'id')->where('approval_status', 'approved')],
            'org_unit_id' => ['required', Rule::exists('org_units', 'id')->where('semester_id', $semester->id)],
            'role' => ['required', Rule::in(['vice_president', 'team_leader'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $actor = $request->user();
        $unit = OrgUnit::query()->findOrFail($data['org_unit_id']);
        abort_unless(
            ($data['role'] === 'vice_president' && $unit->type === 'portfolio')
            || ($data['role'] === 'team_leader' && $unit->type === 'team'),
            422,
            'A szerepkörhöz nem megfelelő szervezeti egység tartozik.'
        );
        if ($data['role'] === 'vice_president' && ! $actor->isPresident()) {
            abort(403);
        }
        if ($data['role'] === 'team_leader' && ! AccessScope::appointsTeamLeader($actor, $unit)) {
            abort(403);
        }

        // One Alelnök per portfolio and one Teamvezető per Team (SZMSZ 12.2-12.3).
        // A replacement starts with revoking the current holder.
        $holder = RoleAssignment::query()
            ->where('semester_id', $semester->id)
            ->where('org_unit_id', $unit->id)
            ->where('role', $data['role'])
            ->whereNull('revoked_at')
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', today()))
            ->with('user:id,name')
            ->first();
        if ($holder) {
            throw ValidationException::withMessages([
                'user_id' => ($data['role'] === 'vice_president' ? 'Ennek a portfóliónak' : 'Ennek a Teamnek')
                    .' már van '.($data['role'] === 'vice_president' ? 'alelnöke' : 'teamvezetője')
                    .' ('.$holder->user->name.'). Előbb vond vissza a kinevezését.',
            ]);
        }

        $role = RoleAssignment::query()->create(array_merge($data, ['semester_id' => $semester->id, 'appointed_by' => $actor->id, 'starts_at' => now()->isAfter($semester->starts_at) ? now()->toDateString() : $semester->starts_at->toDateString(), 'ends_at' => $semester->ends_at]));
        Audit::record($role, 'appointed');

        return back()->with('success', 'A kinevezés rögzítve.');
    }

    public function revoke(Request $request, RoleAssignment $roleAssignment): RedirectResponse
    {
        $actor = $request->user();
        abort_unless((int) $roleAssignment->semester_id === (int) Semester::activeOrFail()->id && ! $roleAssignment->revoked_at, 404);
        if ($roleAssignment->role === 'vice_president' && ! $actor->isPresident()) {
            abort(403);
        }
        if ($roleAssignment->role === 'team_leader') {
            abort_unless($roleAssignment->orgUnit && AccessScope::appointsTeamLeader($actor, $roleAssignment->orgUnit), 403);
        } elseif ($roleAssignment->role !== 'vice_president' && ! AccessScope::managesUnit($actor, $roleAssignment->org_unit_id)) {
            abort(403);
        }

        $before = $roleAssignment->toArray();
        $roleAssignment->update(['revoked_at' => now(), 'ends_at' => now()->toDateString()]);
        Audit::record($roleAssignment, 'revoked', $before);

        return back()->with('success', 'A kinevezés visszavonva.');
    }

    public function assignMember(Request $request): RedirectResponse
    {
        $semester = Semester::activeOrFail();
        $data = $request->validate([
            'user_id' => ['required', Rule::exists('users', 'id')->where('approval_status', 'approved')],
            'org_unit_id' => ['required', Rule::exists('org_units', 'id')->where(fn ($query) => $query->where('semester_id', $semester->id)->where('type', 'team')->where('is_active', true))],
        ]);
        if (! AccessScope::managesUnit($request->user(), (int) $data['org_unit_id'])) {
            abort(403);
        }

        $membership = TeamMembership::query()->updateOrCreate(
            ['semester_id' => $semester->id, 'user_id' => $data['user_id']],
            ['org_unit_id' => $data['org_unit_id'], 'assigned_by' => $request->user()->id, 'starts_at' => now()->toDateString(), 'ends_at' => $semester->ends_at]
        );
        Audit::record($membership, 'team_assigned');

        return back()->with('success', 'A Team-tagság frissítve.');
    }

    public function storeProject(Request $request): RedirectResponse
    {
        $semester = Semester::activeOrFail();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'], 'description' => ['nullable', 'string', 'max:3000'],
            'org_unit_id' => ['nullable', Rule::exists('org_units', 'id')->where(fn ($query) => $query->where('semester_id', $semester->id)->where('is_active', true))], 'lead_user_id' => ['required', Rule::exists('users', 'id')->where('approval_status', 'approved')],
            'member_ids' => ['array', 'max:250'], 'member_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')->where('approval_status', 'approved')], 'ends_at' => ['nullable', 'date', 'after_or_equal:today', 'before_or_equal:'.$semester->ends_at->toDateString()],
        ]);
        // SZMSZ 12.5: creating a supplementary post is the Elnökség's call.
        abort_unless($request->user()->isPresident(), 403);

        $project = Project::query()->create(array_merge(collect($data)->except('member_ids')->all(), ['semester_id' => $semester->id, 'created_by' => $request->user()->id, 'starts_at' => now()->toDateString(), 'status' => 'active']));
        $memberIds = collect($data['member_ids'] ?? [])->push($data['lead_user_id'])->unique();
        $project->members()->sync($memberIds);
        RoleAssignment::query()->create(['semester_id' => $semester->id, 'user_id' => $data['lead_user_id'], 'appointed_by' => $request->user()->id, 'role' => 'project_leader', 'starts_at' => now(), 'ends_at' => $data['ends_at'] ?? $semester->ends_at]);

        foreach ($memberIds->reject(fn ($id) => (int) $id === (int) $data['lead_user_id']) as $memberId) {
            $this->recordProjectMemberRole($project, (int) $memberId, $request->user()->id);
        }

        Audit::record($project, 'created');

        return back()->with('success', 'A projekt létrejött.');
    }

    /**
     * Add someone to a Projekt.
     *
     * AccessScope::managesProject is the Projektvezető and the Elnök, which is
     * exactly the rule: the Elnök creates the Projekt and appoints its leader,
     * the leader picks the team.
     */
    public function addProjectMember(Request $request, Project $project): RedirectResponse
    {
        abort_unless(AccessScope::managesProject($request->user(), $project->id), 403);
        abort_unless($project->status === 'active', 404);

        $data = $request->validate([
            'user_id' => ['required', Rule::exists('users', 'id')->where('approval_status', 'approved')],
        ]);

        if ($project->members()->whereKey($data['user_id'])->exists()) {
            return back()->withErrors(['user_id' => 'Ez a tag már a projekt tagja.']);
        }

        $project->members()->attach($data['user_id'], ['assigned_by' => $request->user()->id]);
        $this->recordProjectMemberRole($project, (int) $data['user_id'], $request->user()->id);
        Audit::record($project, 'project_member_added');

        return back()->with('success', 'A projekttag hozzáadva.');
    }

    /** Remove someone from a Projekt and close their tisztség for the semester. */
    public function removeProjectMember(Request $request, Project $project, User $user): RedirectResponse
    {
        abort_unless(AccessScope::managesProject($request->user(), $project->id), 403);
        abort_unless((int) $project->lead_user_id !== (int) $user->id, 422);

        $project->members()->detach($user->id);

        RoleAssignment::query()
            ->where('semester_id', $project->semester_id)
            ->where('user_id', $user->id)
            ->where('role', 'project_member')
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        Audit::record($project, 'project_member_removed');

        return back()->with('success', 'A projekttag eltávolítva.');
    }

    /**
     * A Projekt tisztség is a dated role assignment, not just a pivot row, so
     * SZMSZ 8.4 accounting (four semesters of accepted tisztség for the FAKT
     * Diploma) has something to count.
     */
    private function recordProjectMemberRole(Project $project, int $userId, int $appointedBy): void
    {
        $exists = RoleAssignment::query()
            ->where('semester_id', $project->semester_id)
            ->where('user_id', $userId)
            ->where('role', 'project_member')
            ->whereNull('revoked_at')
            ->exists();

        if ($exists) {
            return;
        }

        RoleAssignment::query()->create([
            'semester_id' => $project->semester_id,
            'user_id' => $userId,
            'appointed_by' => $appointedBy,
            'role' => 'project_member',
            'starts_at' => today(),
            'ends_at' => $project->ends_at,
        ]);
    }
}
