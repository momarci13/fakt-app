<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\EnrollmentRequest;
use App\Models\Event;
use App\Models\Project;
use App\Models\Semester;
use App\Models\TeamMembership;
use App\Models\User;
use App\Support\AccessScope;
use App\Support\Audit;
use App\Support\CsvExport;
use App\Support\EventCheckIn;
use App\Support\IcsCalendar;
use App\Support\PersonalCalendar;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CalendarController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $statuses = PersonalCalendar::courseStatuses($user);
        $managedProjectIds = $this->managedProjectIds($user);
        $managesCourses = AccessScope::managesCourses($user);
        $events = PersonalCalendar::events($user)->map(fn (Event $event) => array_merge($event->toArray(), [
            'enrollment_status' => $event->course_offering_id ? ($statuses[$event->course_offering_id] ?? null) : null,
            'can_manage' => $this->canManage($user, $event, $managedProjectIds, $managesCourses),
        ]));

        return Inertia::render('Calendar/Index', [
            'events' => $events,
            'canCreate' => $user->isLeader(),
            'members' => ($user->isLeader() || $managesCourses) ? User::query()->where('approval_status', 'approved')->orderBy('name')->get(['id', 'name']) : [],
            'calendarUrl' => $user->calendar_token ? route('calendar.feed', $user->calendar_token) : null,
        ]);
    }

    /** One event as an .ics file, for "add to my calendar" without subscribing. */
    public function download(Request $request, Event $event): HttpResponse
    {
        abort_unless(PersonalCalendar::contains($request->user(), $event), 404);
        $calendar = (new IcsCalendar('FAKT'))->addEvent($event, PersonalCalendar::courseStatuses($request->user())->all());

        return response($calendar->render(), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="fakt-esemeny-'.$event->id.'.ics"',
        ]);
    }

    /** Attendance sheet (CSV) for whoever may finalise attendance. */
    public function attendanceSheet(Request $request, Event $event): StreamedResponse
    {
        $user = $request->user();
        abort_unless($this->canManage($user, $event, $this->managedProjectIds($user), AccessScope::managesCourses($user)), 403);

        $attendances = $event->attendances()->with('user:id,name,email')->get()->keyBy('user_id');
        $participantIds = $attendances->keys();
        if ($event->course_offering_id) {
            $participantIds = $participantIds->merge(EnrollmentRequest::query()->where('course_offering_id', $event->course_offering_id)->whereIn('status', PersonalCalendar::COURSE_CALENDAR_STATUSES)->pluck('user_id'));
        }
        if ($event->org_unit_id) {
            $participantIds = $participantIds->merge(TeamMembership::query()->where('org_unit_id', $event->org_unit_id)->pluck('user_id'));
        }
        $participants = User::query()->whereIn('id', $participantIds->unique())->orderBy('name')->get(['id', 'name', 'email']);

        return CsvExport::download('jelenleti-iv-'.$event->id.'.csv', ['Név', 'Email', 'Visszajelzés', 'Végleges jelenlét', 'QR-bejelentkezés'], $participants->map(function (User $participant) use ($attendances) {
            $attendance = $attendances->get($participant->id);

            return [$participant->name, $participant->email, $attendance?->rsvp_status ?? '', $attendance?->final_status ?? '', $attendance?->checked_in_at?->format('Y-m-d H:i') ?? ''];
        }));
    }

    /** The organiser's screen with the rotating check-in QR code. */
    public function checkInScreen(Request $request, Event $event): Response
    {
        $user = $request->user();
        abort_unless($this->canManage($user, $event, $this->managedProjectIds($user), AccessScope::managesCourses($user)), 403);
        $url = route('calendar.checkin.show', [$event, EventCheckIn::code($event)]);
        $renderer = new ImageRenderer(new RendererStyle(320, 1), new SvgImageBackEnd());

        return Inertia::render('Calendar/CheckIn', [
            'event' => $event->only(['id', 'title', 'starts_at', 'ends_at', 'location']),
            'qrSvg' => (string) preg_replace('/^<\?xml[^>]*>\s*/', '', (new Writer($renderer))->writeString($url)),
            'url' => $url,
            'open' => EventCheckIn::isOpen($event),
            'checkedIn' => $event->attendances()->whereNotNull('checked_in_at')->with('user:id,name')->latest('checked_in_at')->get(['id', 'user_id', 'checked_in_at']),
        ]);
    }

    public function checkInConfirm(Request $request, Event $event, string $code): Response
    {
        return Inertia::render('Calendar/CheckInConfirm', [
            'event' => $event->only(['id', 'title', 'starts_at', 'ends_at', 'location']),
            'code' => $code,
            'valid' => EventCheckIn::isOpen($event) && EventCheckIn::isValid($event, $code) && PersonalCalendar::contains($request->user(), $event),
        ]);
    }

    public function checkIn(Request $request, Event $event, string $code): RedirectResponse
    {
        abort_unless(PersonalCalendar::contains($request->user(), $event), 404);
        if (! EventCheckIn::isOpen($event) || ! EventCheckIn::isValid($event, $code)) {
            return redirect('/naptar')->with('error', 'A QR-kód lejárt vagy az esemény nem fogad bejelentkezést. Olvasd be újra a kivetített kódot.');
        }

        $attendance = Attendance::query()->firstOrNew(['event_id' => $event->id, 'user_id' => $request->user()->id]);
        $attendance->fill(['rsvp_status' => 'attending', 'checked_in_at' => now()]);
        // A final status set by the organiser wins over self check-in.
        if (! $attendance->final_status) {
            $attendance->final_status = 'present';
        }
        $attendance->save();
        Audit::record($attendance, 'checked_in');

        return redirect('/naptar')->with('success', 'Bejelentkeztél: '.$event->title);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isLeader(), 403);
        $semester = Semester::activeOrFail();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'], 'type' => ['required', Rule::in(['assembly', 'community', 'professional', 'team', 'project', 'course', 'workshop', 'alumni'])],
            'starts_at' => ['required', 'date'], 'ends_at' => ['required', 'date', 'after:starts_at'], 'location' => ['nullable', 'string', 'max:160'],
            'visibility' => ['required', Rule::in(['company', 'members', 'scope', 'alumni'])], 'obligation' => ['required', Rule::in(['required', 'optional'])],
            'org_unit_id' => ['nullable', Rule::exists('org_units', 'id')->where('semester_id', $semester->id)], 'project_id' => ['nullable', Rule::exists('projects', 'id')->where('semester_id', $semester->id)], 'description' => ['nullable', 'string', 'max:4000'],
        ]);
        if (! $request->user()->isPresident() && ! AccessScope::managesUnit($request->user(), $data['org_unit_id'] ?? null) && ! AccessScope::managesProject($request->user(), $data['project_id'] ?? null)) {
            abort(403);
        }

        $event = Event::query()->create(array_merge($data, ['semester_id' => $semester->id, 'organizer_id' => $request->user()->id]));
        Audit::record($event, 'created');

        return back()->with('success', 'Az esemény létrejött.');
    }

    public function rsvp(Request $request, Event $event): RedirectResponse
    {
        abort_unless(PersonalCalendar::contains($request->user(), $event), 404);
        $data = $request->validate(['rsvp_status' => ['required', Rule::in(['attending', 'not_attending', 'excused_requested'])], 'excuse_reason' => ['nullable', 'required_if:rsvp_status,excused_requested', 'string', 'max:2000']]);
        $attendance = Attendance::query()->updateOrCreate(['event_id' => $event->id, 'user_id' => $request->user()->id], $data);
        Audit::record($attendance, 'rsvp_updated');

        return back()->with('success', 'A visszajelzés mentve.');
    }

    public function finalize(Request $request, Event $event): RedirectResponse
    {
        $actor = $request->user();
        // Organiser, unit or Projekt leader, or (for course sessions) the KTSZT.
        abort_unless($this->canManage($actor, $event, $this->managedProjectIds($actor), AccessScope::managesCourses($actor)), 403);
        $data = $request->validate(['user_id' => ['required', Rule::exists('users', 'id')->where('approval_status', 'approved')], 'final_status' => ['required', Rule::in(['present', 'absent', 'excused'])]]);
        $participant = User::query()->findOrFail($data['user_id']);
        abort_unless(PersonalCalendar::contains($participant, $event), 422, 'A felhasználó nem résztvevője az eseménynek.');
        $attendance = Attendance::query()->updateOrCreate(['event_id' => $event->id, 'user_id' => $data['user_id']], ['final_status' => $data['final_status'], 'finalized_by' => $request->user()->id, 'finalized_at' => now()]);
        Audit::record($attendance, 'attendance_finalized');

        return back()->with('success', 'A jelenlét véglegesítve.');
    }

    public function updateMeeting(Request $request, Event $event): RedirectResponse
    {
        abort_unless($event->type === 'assembly', 422);
        abort_unless((int) $event->semester_id === (int) Semester::activeOrFail()->id, 404);
        abort_unless((int) $event->organizer_id === (int) $request->user()->id || $request->user()->isPresident() || AccessScope::managesUnit($request->user(), $event->org_unit_id), 403);
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

    /** @return Collection<int, int> */
    private function managedProjectIds(User $user): Collection
    {
        $semesterId = Semester::active()?->id;
        if ($user->isPresident()) {
            return Project::query()->where('semester_id', $semesterId)->pluck('id');
        }

        return Project::query()->where('semester_id', $semesterId)
            ->where(fn ($q) => $q->where('lead_user_id', $user->id)->orWhereIn('org_unit_id', $user->managedOrgUnitIds()))
            ->pluck('id');
    }

    private function canManage(User $user, Event $event, Collection $managedProjectIds, bool $managesCourses): bool
    {
        return (int) $event->organizer_id === (int) $user->id
            || $user->isPresident()
            || ($event->org_unit_id && $user->managedOrgUnitIds()->contains($event->org_unit_id))
            || ($event->project_id && $managedProjectIds->contains($event->project_id))
            || ($event->course_offering_id && $managesCourses);
    }

    public function rotateToken(Request $request): RedirectResponse
    {
        $request->user()->update(['calendar_token' => Str::random(48)]);

        return back()->with('success', 'Új privát naptárhivatkozás készült.');
    }
}
