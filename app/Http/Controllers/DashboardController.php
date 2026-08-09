<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Semester;
use App\Models\Task;
use App\Support\LifecycleProgress;
use App\Support\PersonalCalendar;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user()->load('profile');
        $semester = Semester::active();
        $events = PersonalCalendar::events($user, $semester)->where('ends_at', '>=', now())->take(5)->values();
        $tasks = Task::query()->visibleTo($user)->with('assignees:id,name')->whereNotIn('status', ['done', 'cancelled'])->orderBy('due_at')->take(6)->get();
        $announcements = Announcement::query()->with('author:id,name')->whereNotNull('published_at')->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->orderByDesc('is_pinned')->orderByDesc('published_at')->take(4)->get();

        return Inertia::render('Dashboard', [
            'semester' => $semester,
            'roles' => $user->activeRoleNames(),
            'events' => $events,
            'tasks' => $tasks,
            'progress' => LifecycleProgress::for($user, $semester),
            'announcements' => $announcements,
        ]);
    }
}
