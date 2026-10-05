<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\OrgUnit;
use App\Models\Project;
use App\Models\Semester;
use App\Models\TeamMembership;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Vezetői áttekintés: per Team or Projekt the leader manages, each member's
 * open and overdue tasks, attendance rate on required events (present ÷
 * finalised), and required events in the next 14 days still without an RSVP.
 * All figures come from a fixed number of grouped queries, not per member.
 */
class LeaderOverviewController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->isLeader(), 403);
        $semester = Semester::activeOrFail();

        $teams = OrgUnit::query()->where('semester_id', $semester->id)->where('type', 'team')
            ->whereIn('id', $user->managedOrgUnitIds())->orderBy('name')->get(['id', 'name', 'color']);
        $projects = Project::query()->where('semester_id', $semester->id)->where('status', 'active')
            ->when(! $user->isPresident(), fn ($q) => $q->where('lead_user_id', $user->id))
            ->with('members:id')->orderBy('name')->get(['id', 'name']);

        $teamMembers = TeamMembership::query()->where('semester_id', $semester->id)->whereIn('org_unit_id', $teams->modelKeys())->get(['org_unit_id', 'user_id']);
        $groups = $teams->map(fn (OrgUnit $team) => ['key' => 'team-'.$team->id, 'name' => $team->name, 'kind' => 'Team', 'color' => $team->color, 'org_unit_id' => $team->id, 'project_id' => null, 'user_ids' => $teamMembers->where('org_unit_id', $team->id)->pluck('user_id')->all()])
            ->concat($projects->map(fn (Project $project) => ['key' => 'project-'.$project->id, 'name' => $project->name, 'kind' => 'Projekt', 'color' => null, 'org_unit_id' => null, 'project_id' => $project->id, 'user_ids' => $project->members->modelKeys()]));

        $userIds = $groups->pluck('user_ids')->flatten()->unique()->values();
        $names = User::query()->whereIn('id', $userIds)->where('approval_status', 'approved')->pluck('name', 'id');
        $stats = $this->stats($semester, $userIds, $groups);

        return Inertia::render('Leader/Index', [
            'groups' => $groups->map(fn (array $group) => [
                'key' => $group['key'], 'name' => $group['name'], 'kind' => $group['kind'], 'color' => $group['color'],
                'members' => collect($group['user_ids'])->filter(fn ($id) => $names->has($id))->map(fn ($id) => array_merge(['id' => $id, 'name' => $names[$id]], $stats[$group['key']][$id] ?? []))->sortBy('name')->values(),
            ])->values(),
        ]);
    }

    /** @return array<string, array<int, array<string, int|float|null>>> */
    private function stats(Semester $semester, Collection $userIds, Collection $groups): array
    {
        if ($userIds->isEmpty()) {
            return [];
        }

        $tasks = DB::table('task_assignees')
            ->join('tasks', 'tasks.id', '=', 'task_assignees.task_id')
            ->where('tasks.semester_id', $semester->id)
            ->whereIn('task_assignees.user_id', $userIds)
            ->whereNotIn('tasks.status', ['done', 'cancelled'])
            ->selectRaw('task_assignees.user_id, count(*) as open_count, sum(case when tasks.due_at < ? then 1 else 0 end) as overdue_count', [now()])
            ->groupBy('task_assignees.user_id')
            ->get()->keyBy('user_id');

        $attendance = Attendance::query()
            ->join('events', 'events.id', '=', 'attendances.event_id')
            ->where('events.semester_id', $semester->id)
            ->where('events.obligation', 'required')
            ->whereIn('attendances.user_id', $userIds)
            ->whereNotNull('attendances.final_status')
            ->selectRaw("attendances.user_id, count(*) as finalised, sum(case when attendances.final_status = 'present' then 1 else 0 end) as present")
            ->groupBy('attendances.user_id')
            ->get()->keyBy('user_id');

        $upcoming = Event::query()->where('semester_id', $semester->id)->where('obligation', 'required')->where('status', 'scheduled')
            ->whereBetween('starts_at', [now(), now()->addDays(14)])
            ->get(['id', 'org_unit_id', 'project_id', 'visibility']);
        $answered = Attendance::query()->whereIn('event_id', $upcoming->modelKeys())->whereIn('user_id', $userIds)->where('rsvp_status', '!=', 'pending')
            ->get(['event_id', 'user_id'])->groupBy('user_id')->map(fn ($rows) => $rows->pluck('event_id')->flip());

        $result = [];
        foreach ($groups as $group) {
            $relevant = $upcoming->filter(fn (Event $event) => in_array($event->visibility, ['company', 'members'], true)
                || ($group['org_unit_id'] && (int) $event->org_unit_id === (int) $group['org_unit_id'])
                || ($group['project_id'] && (int) $event->project_id === (int) $group['project_id']));

            foreach ($group['user_ids'] as $id) {
                $row = $attendance->get($id);
                $result[$group['key']][$id] = [
                    'open_tasks' => (int) ($tasks->get($id)->open_count ?? 0),
                    'overdue_tasks' => (int) ($tasks->get($id)->overdue_count ?? 0),
                    'attendance_rate' => $row && $row->finalised > 0 ? round(100 * $row->present / $row->finalised) : null,
                    'missing_rsvps' => $relevant->reject(fn (Event $event) => isset($answered[$id][$event->id]))->count(),
                ];
            }
        }

        return $result;
    }
}
