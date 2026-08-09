<?php

namespace App\Http\Controllers;

use App\Models\CourseOffering;
use App\Models\EnrollmentRequest;
use App\Models\Event;
use App\Models\Semester;
use App\Notifications\FaktNotification;
use App\Support\AccessScope;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function publicIndex(): Response
    {
        $semester = Semester::active();

        return Inertia::render('Courses/Public', [
            'semester' => $semester,
            'courses' => CourseOffering::query()
                ->where('semester_id', $semester?->id)
                ->where('status', 'published')
                ->select(['id', 'title', 'category', 'description', 'instructor_name', 'capacity', 'starts_at', 'ends_at', 'location'])
                ->orderBy('starts_at')
                ->get(),
        ]);
    }

    public function index(Request $request): Response
    {
        $semester = Semester::active();
        $courses = CourseOffering::query()->where('semester_id', $semester?->id)
            ->withCount(['enrollments as approved_count' => fn ($q) => $q->where('status', 'approved')])
            ->with(['enrollments' => fn ($q) => $q->where('user_id', $request->user()->id)])
            ->orderBy('starts_at')->get();

        $canManage = AccessScope::managesCourses($request->user());

        return Inertia::render('Courses/Index', [
            'semester' => $semester,
            'courses' => $courses,
            'canManage' => $canManage,
            'pendingEnrollments' => $canManage ? EnrollmentRequest::query()->whereHas('course', fn ($q) => $q->where('semester_id', $semester?->id))->whereIn('status', ['pending', 'waitlisted'])->with(['user:id,name', 'course:id,title,capacity'])->orderBy('preference_rank')->get() : [],
        ]);
    }

    public function request(Request $request, CourseOffering $course): RedirectResponse
    {
        $semester = Semester::activeOrFail();
        abort_unless($course->semester_id === $semester->id && $semester->course_selection_open, 403);
        $data = $request->validate(['preference_rank' => ['required', 'integer', 'between:1,9']]);
        $hasConflict = EnrollmentRequest::query()
            ->where('user_id', $request->user()->id)
            ->where('course_offering_id', '!=', $course->id)
            ->whereNotIn('status', ['rejected'])
            ->whereHas('course', fn ($query) => $query
                ->where('starts_at', '<', $course->ends_at)
                ->where('ends_at', '>', $course->starts_at))
            ->exists();
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
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'], 'category' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string', 'max:4000'],
            'instructor_name' => ['required', 'string', 'max:160'], 'instructor_email' => ['nullable', 'email', 'max:255'], 'capacity' => ['required', 'integer', 'between:1,100'],
            'starts_at' => ['required', 'date'], 'ends_at' => ['required', 'date', 'after:starts_at'], 'location' => ['nullable', 'string', 'max:160'], 'recurrence_rule' => ['nullable', 'string', 'max:255'],
        ]);
        $course = CourseOffering::query()->create([...$data, 'semester_id' => $semester->id, 'created_by' => $request->user()->id, 'status' => 'published']);
        Event::query()->create(['semester_id' => $semester->id, 'course_offering_id' => $course->id, 'organizer_id' => $request->user()->id, 'title' => $course->title, 'type' => 'course', 'starts_at' => $course->starts_at, 'ends_at' => $course->ends_at, 'location' => $course->location, 'visibility' => 'scope', 'obligation' => 'required', 'description' => $course->description]);
        Audit::record($course, 'created');

        return back()->with('success', 'A kurzus és első alkalma létrejött.');
    }

    public function review(Request $request, EnrollmentRequest $enrollment): RedirectResponse
    {
        abort_unless(AccessScope::managesCourses($request->user()), 403);
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected', 'waitlisted'])], 'decision_note' => ['nullable', 'string', 'max:1000']]);
        $course = $enrollment->course;
        if ($data['status'] === 'approved' && $course->enrollments()->where('status', 'approved')->whereKeyNot($enrollment->id)->count() >= $course->capacity) {
            return back()->withErrors(['status' => 'A kurzus elérte a férőhelylimitet.']);
        }

        $before = $enrollment->toArray();
        $enrollment->update([...$data, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        $enrollment->user->notify(new FaktNotification('Kurzusjelentkezés elbírálva', $course->title.': '.$data['status'], '/kurzusok'));
        Audit::record($enrollment, 'reviewed', $before);

        return back()->with('success', 'A jelentkezés elbírálva.');
    }
}
