<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Semester;
use App\Models\Task;
use App\Models\User;
use App\Notifications\FaktNotification;
use App\Support\AccessScope;
use App\Support\Audit;
use App\Support\TaskDelegation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function __construct(private readonly TaskDelegation $delegation)
    {
    }

    public function index(Request $request): Response
    {
        $semester = Semester::active();
        $semesterId = $semester ? $semester->id : null;
        $tasks = Task::query()->visibleTo($request->user())->where('semester_id', $semesterId)->with(['creator:id,name', 'assignees:id,name', 'orgUnit:id,name,color', 'project:id,name', 'comments.user:id,name'])->orderBy('due_at')->get();
        $units = collect();

        if ($semester) {
            $unitsQuery = $semester->orgUnits();
            $units = $request->user()->isPresident()
                ? $unitsQuery->where('type', 'team')->get(['id', 'name'])
                : $unitsQuery->whereIn('id', $request->user()->managedOrgUnitIds())->get(['id', 'name']);
        }

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'canAssign' => $this->delegation->assignableIdsFor($request->user())->count() > 1,
            'assignees' => $this->delegation->optionsFor($request->user()),
            'delegation' => $this->delegation->summaryFor($request->user()),
            'units' => $units,
            'projects' => $this->availableProjects($request->user(), $semesterId),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $semester = Semester::activeOrFail();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'], 'description' => ['nullable', 'string', 'max:4000'],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])], 'due_at' => ['nullable', 'date'],
            'org_unit_id' => ['nullable', Rule::exists('org_units', 'id')->where('semester_id', $semester->id)], 'project_id' => ['nullable', Rule::exists('projects', 'id')->where('semester_id', $semester->id)],
            'assignee_ids' => ['required', 'array', 'min:1', 'max:50'], 'assignee_ids.*' => ['integer', 'distinct', 'exists:users,id'], 'parent_id' => ['nullable', Rule::exists('tasks', 'id')->where('semester_id', $semester->id)],
        ]);
        $actor = $request->user();
        if (($data['parent_id'] ?? null) && ! Task::query()->visibleTo($actor)->whereKey($data['parent_id'])->exists()) {
            abort(403);
        }
        if (($data['org_unit_id'] ?? null) && ! AccessScope::managesUnit($actor, $data['org_unit_id'])) {
            abort(403);
        }
        if (($data['project_id'] ?? null) && ! AccessScope::managesProject($actor, $data['project_id'])) {
            abort(403);
        }
        $allowed = $this->delegation->assignableIdsFor($actor);
        abort_if(collect($data['assignee_ids'])->diff($allowed)->isNotEmpty(), 403);

        $task = Task::query()->create(array_merge(collect($data)->except('assignee_ids')->all(), ['semester_id' => $semester->id, 'created_by' => $actor->id, 'status' => 'todo', 'visibility' => 'scope']));
        $task->assignees()->sync($data['assignee_ids']);
        User::query()->whereIn('id', $data['assignee_ids'])->whereKeyNot($actor->id)->get()->each->notify(new FaktNotification('Új feladatot kaptál', $task->title, '/feladatok'));
        Audit::record($task, 'created');

        return back()->with('success', 'A feladat létrejött.');
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $actor = $request->user();
        abort_unless((int) $task->semester_id === (int) Semester::activeOrFail()->id, 404);
        $isAssignee = $task->assignees()->where('users.id', $actor->id)->exists();
        abort_unless($actor->isPresident() || (int) $task->created_by === (int) $actor->id || $isAssignee || AccessScope::managesUnit($actor, $task->org_unit_id) || AccessScope::managesProject($actor, $task->project_id), 403);
        $data = $request->validate(['status' => ['required', Rule::in(['todo', 'in_progress', 'review', 'done', 'cancelled'])]]);
        $before = $task->toArray();
        $task->update($data);
        Audit::record($task, 'status_updated', $before);

        return back()->with('success', 'A feladat állapota frissült.');
    }

    public function comment(Request $request, Task $task): RedirectResponse
    {
        abort_unless((int) $task->semester_id === (int) Semester::activeOrFail()->id, 404);
        abort_unless(Task::query()->visibleTo($request->user())->whereKey($task->id)->exists(), 403);
        $data = $request->validate(['body' => ['required', 'string', 'max:3000']]);
        $task->comments()->create(array_merge($data, ['user_id' => $request->user()->id]));

        return back()->with('success', 'A hozzászólás elküldve.');
    }

    private function availableProjects(User $user, ?int $semesterId)
    {
        if ($user->isPresident()) {
            return Project::query()->where('semester_id', $semesterId)->where('status', 'active')->orderBy('name')->get(['id', 'name']);
        }

        $unitIds = $user->managedOrgUnitIds();

        return Project::query()
            ->where('semester_id', $semesterId)
            ->where('status', 'active')
            ->where(fn ($query) => $query
                ->where('lead_user_id', $user->id)
                ->orWhereIn('org_unit_id', $unitIds)
                ->orWhereHas('members', fn ($members) => $members->where('users.id', $user->id)))
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
