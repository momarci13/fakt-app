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
        ]);
    }

    public function appoint(Request $request): RedirectResponse
    {
        $semester = Semester::activeOrFail();
        $data = $request->validate([
            'user_id' => ['required', Rule::exists('users', 'id')->where('approval_status', 'approved')],
            'org_unit_id' => ['nullable', 'exists:org_units,id'],
            'role' => ['required', Rule::in(['vice_president', 'team_leader'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $actor = $request->user();
        if ($data['role'] === 'vice_president' && ! $actor->isPresident()) {
            abort(403);
        }
        if ($data['role'] === 'team_leader' && ! AccessScope::managesUnit($actor, $data['org_unit_id'])) {
            abort(403);
        }

        $role = RoleAssignment::query()->create(array_merge($data, ['semester_id' => $semester->id, 'appointed_by' => $actor->id, 'starts_at' => now()->isAfter($semester->starts_at) ? now()->toDateString() : $semester->starts_at->toDateString(), 'ends_at' => $semester->ends_at]));
        Audit::record($role, 'appointed');

        return back()->with('success', 'A kinevezés rögzítve.');
    }

    public function revoke(Request $request, RoleAssignment $roleAssignment): RedirectResponse
    {
        $actor = $request->user();
        if ($roleAssignment->role === 'vice_president' && ! $actor->isPresident()) {
            abort(403);
        }
        if ($roleAssignment->role !== 'vice_president' && ! AccessScope::managesUnit($actor, $roleAssignment->org_unit_id)) {
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
        $data = $request->validate(['user_id' => ['required', Rule::exists('users', 'id')->where('approval_status', 'approved')], 'org_unit_id' => ['required', 'exists:org_units,id']]);
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
            'org_unit_id' => ['nullable', 'exists:org_units,id'], 'lead_user_id' => ['required', Rule::exists('users', 'id')->where('approval_status', 'approved')],
            'member_ids' => ['array'], 'member_ids.*' => [Rule::exists('users', 'id')->where('approval_status', 'approved')], 'ends_at' => ['nullable', 'date', 'after_or_equal:today'],
        ]);
        if (! $request->user()->isPresident() && ! AccessScope::managesUnit($request->user(), $data['org_unit_id'] ?? null)) {
            abort(403);
        }

        $project = Project::query()->create(array_merge(collect($data)->except('member_ids')->all(), ['semester_id' => $semester->id, 'created_by' => $request->user()->id, 'starts_at' => now()->toDateString(), 'status' => 'active']));
        $project->members()->sync(collect($data['member_ids'] ?? [])->push($data['lead_user_id'])->unique());
        RoleAssignment::query()->create(['semester_id' => $semester->id, 'user_id' => $data['lead_user_id'], 'appointed_by' => $request->user()->id, 'role' => 'project_leader', 'starts_at' => now(), 'ends_at' => $data['ends_at'] ?? $semester->ends_at]);
        Audit::record($project, 'created');

        return back()->with('success', 'A projekt létrejött.');
    }
}
