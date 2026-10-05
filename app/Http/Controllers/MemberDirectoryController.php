<?php

namespace App\Http\Controllers;

use App\Models\RoleAssignment;
use App\Models\Semester;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

/** Tagnévsor: who is who, with Team, offices and expertise. */
class MemberDirectoryController extends Controller
{
    public const ROLE_LABELS = [
        'president' => 'Elnök',
        'vice_president' => 'Alelnök',
        'team_leader' => 'Teamvezető',
        'project_leader' => 'Projektvezető',
        'project_member' => 'Projekttag',
        'ktszt_member' => 'KTSZT-tag',
    ];

    public function __invoke(): Response
    {
        $semesterId = Semester::active()?->id;

        $users = User::query()
            ->where('approval_status', 'approved')
            ->with([
                'profile:id,user_id,member_status,cohort_year,expertise,alumni_visible,mentor_available',
                'teamMemberships' => fn ($q) => $q->where('semester_id', $semesterId)->with('orgUnit:id,name,color'),
                'roles' => fn ($q) => $q->where('semester_id', $semesterId)->whereNull('revoked_at')
                    ->whereDate('starts_at', '<=', today())
                    ->where(fn ($r) => $r->whereNull('ends_at')->orWhereDate('ends_at', '>=', today()))
                    ->with('orgUnit:id,name'),
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            // Alumni appear only if they opted in to the alumni directory.
            ->reject(fn (User $user) => $user->profile?->member_status === 'alumni' && ! $user->profile?->alumni_visible);

        return Inertia::render('Members/Index', [
            'members' => $users->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->profile?->member_status,
                'cohort_year' => $user->profile?->cohort_year,
                'expertise' => $user->profile?->expertise,
                'mentor' => (bool) $user->profile?->mentor_available,
                'team' => $user->teamMemberships->first()?->orgUnit?->only(['name', 'color']),
                'roles' => $user->roles->map(fn (RoleAssignment $role) => trim((self::ROLE_LABELS[$role->role] ?? $role->role).($role->orgUnit ? ' · '.$role->orgUnit->name : '')))->unique()->values(),
            ])->values(),
        ]);
    }
}
