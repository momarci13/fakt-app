<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\Semester;
use App\Models\User;
use App\Support\AccessScope;
use App\Support\Audit;
use App\Support\PersonalCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function index(Request $request): Response
    {
        $events = PersonalCalendar::events($request->user());

        return Inertia::render('Calendar/Index', [
            'events' => $events,
            'canCreate' => $request->user()->isLeader(),
            'members' => $request->user()->isLeader() ? User::query()->orderBy('name')->get(['id', 'name']) : [],
            'calendarUrl' => $request->user()->calendar_token ? route('calendar.feed', $request->user()->calendar_token) : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isLeader(), 403);
        $semester = Semester::activeOrFail();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'], 'type' => ['required', 'string', 'max:50'],
            'starts_at' => ['required', 'date'], 'ends_at' => ['required', 'date', 'after:starts_at'], 'location' => ['nullable', 'string', 'max:160'],
            'visibility' => ['required', Rule::in(['company', 'members', 'scope', 'alumni'])], 'obligation' => ['required', Rule::in(['required', 'optional'])],
            'org_unit_id' => ['nullable', 'exists:org_units,id'], 'project_id' => ['nullable', 'exists:projects,id'], 'description' => ['nullable', 'string', 'max:4000'],
        ]);
        if (! $request->user()->isPresident() && ! AccessScope::managesUnit($request->user(), $data['org_unit_id'] ?? null) && ! AccessScope::managesProject($request->user(), $data['project_id'] ?? null)) {
            abort(403);
        }

        $event = Event::query()->create([...$data, 'semester_id' => $semester->id, 'organizer_id' => $request->user()->id]);
        Audit::record($event, 'created');

        return back()->with('success', 'Az esemény létrejött.');
    }

    public function rsvp(Request $request, Event $event): RedirectResponse
    {
        $data = $request->validate(['rsvp_status' => ['required', Rule::in(['attending', 'not_attending', 'excused_requested'])], 'excuse_reason' => ['nullable', 'required_if:rsvp_status,excused_requested', 'string', 'max:2000']]);
        $attendance = Attendance::query()->updateOrCreate(['event_id' => $event->id, 'user_id' => $request->user()->id], $data);
        Audit::record($attendance, 'rsvp_updated');

        return back()->with('success', 'A visszajelzés mentve.');
    }

    public function finalize(Request $request, Event $event): RedirectResponse
    {
        abort_unless($event->organizer_id === $request->user()->id || AccessScope::managesUnit($request->user(), $event->org_unit_id), 403);
        $data = $request->validate(['user_id' => ['required', 'exists:users,id'], 'final_status' => ['required', Rule::in(['present', 'absent', 'excused'])]]);
        $attendance = Attendance::query()->updateOrCreate(['event_id' => $event->id, 'user_id' => $data['user_id']], ['final_status' => $data['final_status'], 'finalized_by' => $request->user()->id, 'finalized_at' => now()]);
        Audit::record($attendance, 'attendance_finalized');

        return back()->with('success', 'A jelenlét véglegesítve.');
    }

    public function updateMeeting(Request $request, Event $event): RedirectResponse
    {
        abort_unless($event->type === 'assembly', 422);
        abort_unless($event->organizer_id === $request->user()->id || $request->user()->isPresident() || AccessScope::managesUnit($request->user(), $event->org_unit_id), 403);
        $data = $request->validate([
            'agenda' => ['nullable', 'string', 'max:10000'],
            'minutes' => ['nullable', 'string', 'max:20000'],
            'decision_summary' => ['nullable', 'string', 'max:10000'],
            'quorum_required' => ['nullable', 'integer', 'between:1,250'],
            'participant_count' => ['nullable', 'integer', 'between:0,250'],
        ]);
        $before = $event->toArray();
        $event->update($data);
        Audit::record($event, 'meeting_record_updated', $before);

        return back()->with('success', 'A gyűlési adatok és jegyzőkönyv mentve.');
    }

    public function rotateToken(Request $request): RedirectResponse
    {
        $request->user()->update(['calendar_token' => Str::random(48)]);

        return back()->with('success', 'Új privát naptárhivatkozás készült.');
    }
}
