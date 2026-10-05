<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\CourseDateOption;
use App\Models\CourseOffering;
use App\Models\EnrollmentRequest;
use App\Models\Semester;
use App\Notifications\FaktNotification;
use App\Support\AccessScope;
use App\Support\Audit;
use App\Support\CourseCompletion;
use App\Support\CoursePlacement;
use App\Support\CourseSchedule;
use App\Support\CsvExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CourseController extends Controller
{
    public function publicIndex(): Response
    {
        $semester = Semester::active();

        return Inertia::render('Courses/Public', [
            'semester' => $semester,
            'courses' => CourseOffering::query()
                ->where('semester_id', ($nullsafeVariable1 = $semester) ? $nullsafeVariable1->id : null)
                ->where('status', 'published')
                ->select(['id', 'title', 'category', 'description', 'instructor_name', 'capacity', 'starts_at', 'ends_at', 'location'])
                ->orderBy('starts_at')
                ->get(),
        ]);
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $semester = Semester::active();
        $courses = CourseOffering::query()->where('semester_id', $semester?->id)
            ->withCount([
                'enrollments as approved_count' => fn ($q) => $q->where('status', 'approved'),
                'sessions as sessions_count' => fn ($q) => $q->where('status', 'scheduled'),
            ])
            ->with([
                'enrollments' => fn ($q) => $q->where('user_id', $user->id),
                'dateOptions' => fn ($q) => $q->withCount('voters')->withExists(['voters as voted' => fn ($v) => $v->where('users.id', $user->id)]),
            ])
            ->orderBy('starts_at')->get();

        $canManage = AccessScope::managesCourses($user);

        return Inertia::render('Courses/Index', [
            'semester' => $semester,
            'courses' => $courses,
            'canManage' => $canManage,
            'pendingEnrollments' => $canManage ? EnrollmentRequest::query()->whereHas('course', fn ($q) => $q->where('semester_id', $semester?->id))->whereIn('status', ['pending', 'waitlisted'])->with(['user:id,name', 'course:id,title,capacity'])->orderBy('preference_rank')->get() : [],
            'placement' => $canManage && $semester ? CoursePlacement::propose($semester, $semester->id) : [],
        ]);
    }

    public function request(Request $request, CourseOffering $course): RedirectResponse
    {
        $semester = Semester::activeOrFail();
        abort_unless((int) $course->semester_id === (int) $semester->id && $semester->course_selection_open, 403);
        $data = $request->validate(['preference_rank' => ['required', 'integer', 'between:1,9']]);
        $hasConflict = EnrollmentRequest::query()
            ->where('user_id', $request->user()->id)
            ->where('course_offering_id', '!=', $course->id)
            ->whereNotIn('status', ['rejected'])
            ->with('course')
            ->get()
            ->contains(function (EnrollmentRequest $enrollment) use ($course) {
                return $enrollment->course
                    && $enrollment->course->starts_at->lt($course->ends_at)
                    && $enrollment->course->ends_at->gt($course->starts_at);
            });
        if ($hasConflict) {
            return back()->withErrors(['preference_rank' => 'A kurzus időpontja ütközik egy másik kiválasztott kurzusoddal.']);
        }

        $enrollment = EnrollmentRequest::query()->updateOrCreate(
            ['course_offering_id' => $course->id, 'user_id' => $request->user()->id],
            ['preference_rank' => $data['preference_rank'], 'status' => 'pending', 'reviewed_by' => null, 'reviewed_at' => null, 'decision_note' => null]
        );
        Audit::record($enrollment, 'requested');

        return back()->with('success', 'A kurzusjelentkezést rögzítettük.');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(AccessScope::managesCourses($request->user()), 403);
        $semester = Semester::activeOrFail();
        // Empty rows of the date-option editor are not options.
        $request->merge(['date_options' => collect($request->input('date_options', []))
            ->filter(fn ($option) => is_array($option) && (filled($option['starts_at'] ?? null) || filled($option['ends_at'] ?? null)))
            ->values()->all()]);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'], 'category' => ['required', 'string', 'max:100', 'regex:/^[\pL\pN][\pL\pN .&()\-]{0,99}$/u'], 'description' => ['nullable', 'string', 'max:4000'],
            'instructor_name' => ['required', 'string', 'max:160'], 'instructor_email' => ['nullable', 'email', 'max:255'], 'capacity' => ['required', 'integer', 'between:1,100'],
            'allowed_absences' => ['nullable', 'integer', 'between:0,20'],
            'starts_at' => ['required_without:date_options', 'nullable', 'date'], 'ends_at' => ['required_without:date_options', 'nullable', 'date', 'after:starts_at'], 'location' => ['nullable', 'string', 'max:160'], 'recurrence_rule' => ['nullable', 'string', 'max:255', 'regex:/^FREQ=(?:DAILY|WEEKLY|MONTHLY)(?:;INTERVAL=[1-9][0-9]?)?(?:;COUNT=[1-9][0-9]{0,2})?$/D'],
            'date_options' => ['array', 'max:8'],
            'date_options.*.starts_at' => ['required', 'date'],
            'date_options.*.ends_at' => ['required', 'date'],
            'date_options.*.location' => ['nullable', 'string', 'max:160'],
        ]);

        $options = collect($data['date_options'] ?? [])->sortBy('starts_at')->values();
        foreach ($options as $index => $option) {
            if (strtotime($option['ends_at']) <= strtotime($option['starts_at'])) {
                return back()->withErrors(["date_options.{$index}.ends_at" => 'Az időpont vége a kezdete után legyen.']);
            }
        }
        if ($options->count() === 1) {
            $data = array_merge($data, ['starts_at' => $options[0]['starts_at'], 'ends_at' => $options[0]['ends_at'], 'location' => $options[0]['location'] ?? $data['location'] ?? null]);
            $options = collect();
        }
        $polling = $options->count() >= 2;

        $course = CourseOffering::query()->create(array_merge(
            collect($data)->except(['date_options'])->all(),
            [
                'semester_id' => $semester->id, 'created_by' => $request->user()->id, 'status' => 'published',
                'allowed_absences' => $data['allowed_absences'] ?? 2,
                'schedule_status' => $polling ? 'polling' : 'scheduled',
                // While polling, the earliest option is the tentative date.
                'starts_at' => $polling ? $options[0]['starts_at'] : $data['starts_at'],
                'ends_at' => $polling ? $options[0]['ends_at'] : $data['ends_at'],
            ],
        ));

        if ($polling) {
            foreach ($options as $option) {
                $course->dateOptions()->create($option);
            }
            Audit::record($course, 'created');

            return back()->with('success', 'A kurzus létrejött, az időpontszavazás megnyílt.');
        }

        $sessions = CourseSchedule::sync($course, $request->user()->id);
        Audit::record($course, 'created');

        return back()->with('success', "A kurzus létrejött ({$sessions} alkalom a naptárban).");
    }

    /**
     * Approval voting on the date options: a member ticks every option that
     * works for them. Open to everyone who applied (pending, approved or
     * waitlisted) while the poll is open.
     */
    public function voteDate(Request $request, CourseOffering $course): RedirectResponse
    {
        abort_unless($course->schedule_status === 'polling' && (int) $course->semester_id === (int) Semester::activeOrFail()->id, 404);
        abort_unless($course->enrollments()->where('user_id', $request->user()->id)->whereIn('status', ['pending', 'approved', 'waitlisted'])->exists(), 403, 'Csak a kurzusra jelentkezők szavazhatnak.');
        $data = $request->validate(['option_ids' => ['array', 'max:8'], 'option_ids.*' => ['integer', Rule::exists('course_date_options', 'id')->where('course_offering_id', $course->id)]]);

        $optionIds = $course->dateOptions()->pluck('id');
        DB::transaction(function () use ($request, $optionIds, $data): void {
            DB::table('course_date_votes')->where('user_id', $request->user()->id)->whereIn('course_date_option_id', $optionIds)->delete();
            DB::table('course_date_votes')->insert(collect($data['option_ids'] ?? [])->unique()->map(fn ($id) => [
                'course_date_option_id' => (int) $id, 'user_id' => $request->user()->id, 'created_at' => now(), 'updated_at' => now(),
            ])->values()->all());
        });
        Audit::record($course, 'date_vote');

        return back()->with('success', 'A szavazatod mentve.');
    }

    /** Close the poll on one option; the sessions go into the calendars. */
    public function fixDate(Request $request, CourseOffering $course): RedirectResponse
    {
        abort_unless(AccessScope::managesCourses($request->user()), 403);
        abort_unless($course->schedule_status === 'polling' && (int) $course->semester_id === (int) Semester::activeOrFail()->id, 404);
        $data = $request->validate(['option_id' => ['required', 'integer', Rule::exists('course_date_options', 'id')->where('course_offering_id', $course->id)]]);
        $option = CourseDateOption::query()->findOrFail($data['option_id']);

        $count = CourseSchedule::fixDate($course, $option->starts_at, $option->ends_at, $option->location, $request->user());
        Audit::record($course, 'date_fixed', null, ['option_id' => $option->id]);

        return back()->with('success', "Az időpont kijelölve, {$count} alkalom bekerült a jelentkezők naptárába.");
    }

    /**
     * Apply the placement proposal (CoursePlacement). Nobody decides their
     * own enrollment, so the actor's own requests are left for someone else.
     */
    public function applyPlacement(Request $request): RedirectResponse
    {
        abort_unless(AccessScope::managesCourses($request->user()), 403);
        $semester = Semester::activeOrFail();
        $data = $request->validate(['seed' => ['required', 'integer']]);
        $applied = 0;
        $skipped = 0;

        foreach (CoursePlacement::propose($semester, (int) $data['seed']) as $row) {
            if (! AccessScope::canDecideFor($request->user(), $row['user_id'])) {
                $skipped++;

                continue;
            }
            $enrollment = EnrollmentRequest::query()->findOrFail($row['enrollment_id']);
            if ($enrollment->status === $row['proposal']) {
                continue;
            }
            $before = $enrollment->toArray();
            $enrollment->update(['status' => $row['proposal'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'decision_note' => 'Automatikus beosztás']);
            Audit::record($enrollment, 'placed', $before);
            $enrollment->user?->notify(new FaktNotification(
                'Kurzusbeosztás',
                $row['course'].': '.($row['proposal'] === 'approved' ? 'bekerültél, az alkalmak a naptáradban vannak.' : 'várólistára kerültél.'),
                '/kurzusok'
            ));
            $applied++;
        }

        return back()->with('success', "Beosztás alkalmazva: {$applied} döntés".($skipped ? ", {$skipped} saját jelentkezés kihagyva (összeférhetetlenség)." : '.'));
    }

    /** Roster with attendance, for the course's managers (CSV, Excel-friendly). */
    public function roster(Request $request, CourseOffering $course): StreamedResponse
    {
        abort_unless(AccessScope::managesCourses($request->user()), 403);
        $sessionIds = $course->sessions()->where('status', 'scheduled')->pluck('id');
        $enrollments = $course->enrollments()->with('user:id,name,email')->orderBy('status')->get();
        $absences = Attendance::query()->whereIn('event_id', $sessionIds)->whereIn('final_status', CourseCompletion::COUNTED_ABSENCES)
            ->selectRaw('user_id, count(*) as total')->groupBy('user_id')->pluck('total', 'user_id');

        return CsvExport::download('nevsor-'.$course->id.'.csv', ['Név', 'Email', 'Állapot', 'Preferencia', 'Hiányzás', 'Megengedett'], $enrollments->map(fn (EnrollmentRequest $e) => [
            $e->user?->name, $e->user?->email, $e->status, $e->preference_rank, (int) ($absences[$e->user_id] ?? 0), $course->allowed_absences,
        ]));
    }

    public function review(Request $request, EnrollmentRequest $enrollment): RedirectResponse
    {
        abort_unless(AccessScope::managesCourses($request->user()), 403);
        abort_unless(
            AccessScope::canDecideFor($request->user(), $enrollment->user_id),
            403,
            'Összeférhetetlenség: a saját jelentkezésedről nem dönthetsz.'
        );
        abort_unless((int) $enrollment->course->semester_id === (int) Semester::activeOrFail()->id, 404);
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected', 'waitlisted'])], 'decision_note' => ['nullable', 'string', 'max:1000']]);
        $course = $enrollment->course;
        if ($data['status'] === 'approved' && $course->enrollments()->where('status', 'approved')->whereKeyNot($enrollment->id)->count() >= $course->capacity) {
            return back()->withErrors(['status' => 'A kurzus elérte a férőhelylimitet.']);
        }

        $before = $enrollment->toArray();
        $enrollment->update(array_merge($data, ['reviewed_by' => $request->user()->id, 'reviewed_at' => now()]));
        $message = match ($data['status']) {
            'approved' => 'Jóváhagyva. '.($course->schedule_status === 'scheduled' ? 'Az alkalmak bekerültek a naptáradba.' : 'Az időpontról szavazás dönt.'),
            'waitlisted' => 'Várólistára kerültél. '.($course->schedule_status === 'scheduled' ? 'Az alkalmak várólistásként a naptáradban vannak.' : ''),
            default => 'Elutasítva.',
        };
        $enrollment->user->notify(new FaktNotification('Kurzusjelentkezés elbírálva', $course->title.': '.trim($message), '/kurzusok'));
        Audit::record($enrollment, 'reviewed', $before);

        return back()->with('success', 'A jelentkezés elbírálva.');
    }
}
