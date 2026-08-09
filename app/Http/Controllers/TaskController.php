<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Semester;
use App\Models\Task;
use App\Models\User;
use App\Notifications\FaktNotification;
use App\Support\AccessScope;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        $semester = Semester::active();
        $tasks = Task::query()->visibleTo($request->user())->where('semester_id', $semester?->id)->with(['assignees:id,name', 'orgUnit:id,name,color', 'project:id,name', 'comments.user:id,name'])->orderBy('due_at')->get();

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'canAssign' => $request->user()->isLeader(),
            'assignees' => $this->assignableUsers($request->user()),
            'units' => $request->user()->isPresident() ? $semester?->orgUnits()->where('type', 'team')->get(['id', 'name']) : $semester?->orgUnits()->whereIn('id', $request->user()->managedOrgUnitIds())->get(['id', 'name']),
            'projects' => Project::query()->where('semester_id', $semester?->id)->where(fn ($q) => $q->where('lead_user_id', $request->user()->id)->orWhereHas('members', fn ($m) => $m->where('users.id', $request->user()->id)))->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $semester = Semester::activeOrFail();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'], 'description' => ['nullable', 'string', 'max:4000'],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])], 'due_at' => ['nullable', 'date'],
            'org_unit_id' => ['nullable', 'exists:org_units,id'], 'project_id' => ['nullable', 'exists:projects,id'],
            'assignee_ids' => ['required', 'array', 'min:1'], 'assignee_ids.*' => ['exists:users,id'], 'parent_id' => ['nullable', 'exists:tasks,id'],
        ]);
        $actor = $request->user();
        if (($data['org_unit_id'] ?? null) && ! AccessScope::managesUnit($actor, $data['org_unit_id'])) {
            abort(403);
        }
        if (($data['project_id'] ?? null) && ! AccessScope::managesProject($actor, $data['project_id'])) {
            abort(403);
        }
        $allowed = $this->assignableUsers($actor)->pluck('id');
        abort_if(collect($data['assignee_ids'])->diff($allowed)->isNotEmpty(), 403);

        $task = Task::query()->create([...collect($data)->except('assignee_ids')->all(), 'semester_id' => $semester->id, 'created_by' => $actor->id, 'status' => 'todo', 'visibility' => 'scope']);
        $task->assignees()->sync($data['assignee_ids']);
        User::query()->whereIn('id', $data['assignee_ids'])->whereKeyNot($actor->id)->get()->each->notify(new FaktNotification('Új feladatot kaptál', $task->title, '/feladatok'));
        Audit::record($task, 'created');

        return back()->with('success', 'A feladat létrejött.');
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $actor = $request->user();
        $isAssignee = $task->assignees()->where('users.id', $actor->id)->exists();
        abort_unless($actor->isPresident() || $task->created_by === $actor->id || $isAssignee || AccessScope::managesUnit($actor, $task->org_unit_id) || AccessScope::managesProject($actor, $task->project_id), 403);
        $data = $request->validate(['status' => ['required', Rule::in(['todo', 'in_progress', 'review', 'done', 'cancelled'])]]);
        $before = $task->toArray();
        $task->update($data);
        Audit::record($task, 'status_updated', $before);

        return back()->with('success', 'A feladat állapota frissült.');
    }

    public function comment(Request $request, Task $task): RedirectResponse
    {
        abort_unless(Task::query()->visibleTo($request->user())->whereKey($task->id)->exists(), 403);
        $data = $request->validate(['body' => ['required', 'string', 'max:3000']]);
        $task->comments()->create([...$data, 'user_id' => $request->user()->id]);

        return back()->with('success', 'A hozzászólás elküldve.');
    }

    private function assignableUsers(User $user)
    {
        if ($user->isPresident()) {
            return User::query()->whereHas('profile', fn ($q) => $q->whereIn('member_status', ['active', 'senior']))->orderBy('name')->get(['id', 'name']);
        }
        $unitIds = $user->managedOrgUnitIds();
        $projectUserIds = Project::query()->where('lead_user_id', $user->id)->with('members:id')->get()->flatMap->members->pluck('id');

        return User::query()->whereKey($user->id)->orWhereHas('teamMemberships', fn ($q) => $q->whereIn('org_unit_id', $unitIds))->orWhereIn('id', $projectUserIds)->orderBy('name')->get(['id', 'name']);
    }
}
