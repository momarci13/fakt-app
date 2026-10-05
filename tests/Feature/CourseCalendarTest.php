<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\CourseOffering;
use App\Models\EnrollmentRequest;
use App\Models\Event;
use App\Models\ObligationWaiver;
use App\Models\OrgUnit;
use App\Models\RoleAssignment;
use App\Models\Semester;
use App\Models\User;
use App\Notifications\FaktNotification;
use App\Support\CourseCompletion;
use App\Support\CoursePlacement;
use App\Support\CourseSchedule;
use App\Support\IcsCalendar;
use App\Support\PersonalCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Courses in the personal calendar: one event per session, DST-safe; dated
 * sessions visible to approved and waitlisted members; date polls; placement;
 * absence limit; Elnökség waivers.
 */
class CourseCalendarTest extends TestCase
{
    use RefreshDatabase;

    private Semester $semester;

    private User $president;

    private User $ktszt;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-05 12:00:00');

        $this->semester = Semester::query()->create(['name' => '2026 ősz', 'starts_at' => '2026-07-01', 'ends_at' => '2026-12-31', 'is_active' => true, 'course_selection_open' => true]);
        $this->president = $this->member('Elnök');
        $this->assign($this->president, 'president');
        $this->ktszt = $this->member('KTSZT');
        $this->assign($this->ktszt, 'ktszt_member');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function member(string $name): User
    {
        return User::factory()->create(['name' => $name, 'approval_status' => 'approved', 'calendar_token' => Str::random(48)]);
    }

    private function assign(User $user, string $role, ?int $unitId = null): void
    {
        RoleAssignment::query()->create(['semester_id' => $this->semester->id, 'org_unit_id' => $unitId, 'user_id' => $user->id, 'role' => $role, 'starts_at' => '2026-07-01']);
    }

    private function course(array $attributes = []): CourseOffering
    {
        $course = CourseOffering::query()->create(array_merge([
            'semester_id' => $this->semester->id, 'created_by' => $this->ktszt->id, 'title' => 'Értékpapír-elemzés', 'category' => 'Pénzügy',
            'instructor_name' => 'Oktató', 'capacity' => 2, 'starts_at' => '2026-10-14 18:00:00', 'ends_at' => '2026-10-14 19:30:00',
            'recurrence_rule' => 'FREQ=WEEKLY;COUNT=4', 'status' => 'published',
        ], $attributes));
        CourseSchedule::sync($course, $this->ktszt->id);

        return $course;
    }

    private function enroll(User $user, CourseOffering $course, string $status, int $rank = 1): EnrollmentRequest
    {
        return EnrollmentRequest::query()->create(['course_offering_id' => $course->id, 'user_id' => $user->id, 'status' => $status, 'preference_rank' => $rank]);
    }

    private function confirmed(User $user): static
    {
        return $this->actingAs($user)->withSession(['auth.password_confirmed_at' => now()->unix()]);
    }

    public function test_weekly_sessions_keep_local_time_across_the_october_dst_change(): void
    {
        $course = $this->course();
        $sessions = $course->sessions()->get();

        $this->assertCount(4, $sessions);
        $this->assertSame(['2026-10-14 18:00', '2026-10-21 18:00', '2026-10-28 18:00', '2026-11-04 18:00'], $sessions->map(fn (Event $e) => $e->starts_at->format('Y-m-d H:i'))->all());
        // Summer time (UTC+2) before 25 October, winter time (UTC+1) after.
        $this->assertSame('16:00', $sessions[0]->starts_at->utc()->format('H:i'));
        $this->assertSame('17:00', $sessions[2]->starts_at->utc()->format('H:i'));
        $this->assertSame([1, 2, 3, 4], $sessions->pluck('session_number')->all());
    }

    public function test_a_rule_without_count_runs_to_the_end_of_the_semester(): void
    {
        $occurrences = CourseSchedule::occurrences(Carbon::parse('2026-11-30 10:00'), Carbon::parse('2026-11-30 11:00'), 'FREQ=WEEKLY', Carbon::parse('2026-12-31'));

        $this->assertCount(5, $occurrences);
        $this->assertSame('2026-12-28', end($occurrences)[0]->toDateString());
        $this->assertNull(CourseSchedule::parseRule('FREQ=YEARLY'));
    }

    public function test_resyncing_with_fewer_sessions_cancels_attended_ones_and_deletes_the_rest(): void
    {
        $course = $this->course();
        $student = $this->member('Diák');
        $third = $course->sessions()->where('session_number', 3)->first();
        Attendance::query()->create(['event_id' => $third->id, 'user_id' => $student->id, 'final_status' => 'present']);

        $course->update(['recurrence_rule' => 'FREQ=WEEKLY;COUNT=2']);
        CourseSchedule::sync($course, $this->ktszt->id);

        $this->assertSame('cancelled', $third->fresh()->status);
        $this->assertSame(1, $third->fresh()->sequence);
        $this->assertNull(Event::query()->where('course_offering_id', $course->id)->where('session_number', 4)->first());
    }

    public function test_approved_and_waitlisted_members_see_the_sessions_others_do_not(): void
    {
        $course = $this->course();
        $approved = $this->member('Jóváhagyott');
        $waitlisted = $this->member('Várólistás');
        $pending = $this->member('Függő');
        $this->enroll($approved, $course, 'approved');
        $this->enroll($waitlisted, $course, 'waitlisted');
        $this->enroll($pending, $course, 'pending');

        $this->assertCount(4, PersonalCalendar::events($approved));
        $this->assertCount(4, PersonalCalendar::events($waitlisted));
        $this->assertCount(0, PersonalCalendar::events($pending));

        $this->get(route('calendar.feed', $waitlisted->calendar_token))
            ->assertOk()
            ->assertSee('SUMMARY:[Várólista] Értékpapír-elemzés (1. alkalom)', false)
            ->assertSee('DTSTART:20261014T160000Z', false)
            ->assertSee('DTSTART:20261028T170000Z', false)
            ->assertSee('TRIGGER:-PT60M', false);
    }

    public function test_the_feed_answers_304_when_unchanged(): void
    {
        $user = $this->member('Tag');
        $first = $this->get(route('calendar.feed', $user->calendar_token))->assertOk();
        $etag = $first->headers->get('ETag');

        $this->get(route('calendar.feed', $user->calendar_token), ['If-None-Match' => $etag])->assertStatus(304);
    }

    public function test_long_ics_lines_are_folded_without_breaking_characters(): void
    {
        $folded = IcsCalendar::fold('SUMMARY:'.str_repeat('Árvíztűrő tükörfúrógép ', 6));

        foreach (explode("\r\n", $folded) as $line) {
            $this->assertLessThanOrEqual(75, strlen($line));
            $this->assertTrue(mb_check_encoding($line, 'UTF-8'));
        }
        $this->assertSame('SUMMARY:'.str_repeat('Árvíztűrő tükörfúrógép ', 6), str_replace("\r\n ", '', $folded));
    }

    public function test_date_poll_voting_and_fixing_the_date(): void
    {
        Notification::fake();
        $this->actingAs($this->ktszt)->post(route('courses.store'), [
            'title' => 'Modellezés', 'category' => 'Pénzügy', 'instructor_name' => 'Oktató', 'capacity' => 10,
            'recurrence_rule' => 'FREQ=WEEKLY;COUNT=3',
            'date_options' => [
                ['starts_at' => '2026-10-20T18:00', 'ends_at' => '2026-10-20T19:30'],
                ['starts_at' => '2026-10-22T17:00', 'ends_at' => '2026-10-22T18:30'],
                ['starts_at' => '', 'ends_at' => ''],
            ],
        ])->assertSessionHasNoErrors();

        $course = CourseOffering::query()->where('title', 'Modellezés')->firstOrFail();
        $this->assertSame('polling', $course->schedule_status);
        $this->assertSame(2, $course->dateOptions()->count());
        $this->assertSame(0, $course->sessions()->count());

        $student = $this->member('Diák');
        $outsider = $this->member('Kívülálló');
        $this->enroll($student, $course, 'approved');
        $second = $course->dateOptions()->get()[1];

        $this->actingAs($outsider)->post(route('courses.dates.vote', $course), ['option_ids' => [$second->id]])->assertForbidden();
        $this->actingAs($student)->post(route('courses.dates.vote', $course), ['option_ids' => [$second->id]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('course_date_votes', ['course_date_option_id' => $second->id, 'user_id' => $student->id]);

        $this->actingAs($student)->post(route('courses.dates.fix', $course), ['option_id' => $second->id])->assertForbidden();
        $this->actingAs($this->ktszt)->post(route('courses.dates.fix', $course), ['option_id' => $second->id])->assertSessionHasNoErrors();

        $course->refresh();
        $this->assertSame('scheduled', $course->schedule_status);
        $this->assertSame(3, $course->sessions()->count());
        $this->assertSame('2026-10-22 17:00', $course->sessions()->first()->starts_at->format('Y-m-d H:i'));
        $this->assertCount(3, PersonalCalendar::events($student));
        Notification::assertSentTo($student, FaktNotification::class, fn ($n) => $n->title === 'Kurzusidőpont kijelölve');
    }

    public function test_placement_takes_the_best_ranks_per_course_and_skips_the_actors_own_request(): void
    {
        Notification::fake();
        $course = $this->course(['capacity' => 2]);
        $a = $this->member('A');
        $b = $this->member('B');
        $c = $this->member('C');
        $this->enroll($a, $course, 'pending', 3);
        $this->enroll($b, $course, 'pending', 1);
        $this->enroll($c, $course, 'pending', 2);
        $own = $this->enroll($this->ktszt, $course, 'pending', 1);

        $proposal = CoursePlacement::propose($this->semester, 7)->keyBy('user_id');
        // Ranks 1, 1 take the two seats; ranks 2 and 3 wait.
        $this->assertSame('approved', $proposal[$b->id]['proposal']);
        $this->assertSame('approved', $proposal[$this->ktszt->id]['proposal']);
        $this->assertSame('waitlisted', $proposal[$c->id]['proposal']);
        $this->assertSame('waitlisted', $proposal[$a->id]['proposal']);
        $this->assertEquals($proposal, CoursePlacement::propose($this->semester, 7)->keyBy('user_id'));

        $this->confirmed($this->ktszt)->post(route('courses.placement.apply'), ['seed' => 7])->assertSessionHasNoErrors();

        $this->assertSame('approved', $b->enrollments()->first()->status);
        $this->assertSame('waitlisted', $c->enrollments()->first()->status);
        // Conflict of interest: nobody places themselves.
        $this->assertSame('pending', $own->fresh()->status);
    }

    public function test_two_absences_are_allowed_and_the_third_fails_the_course(): void
    {
        $course = $this->course();
        $student = $this->member('Diák');
        $this->enroll($student, $course, 'approved');
        $sessions = $course->sessions()->get();

        Attendance::query()->create(['event_id' => $sessions[0]->id, 'user_id' => $student->id, 'final_status' => 'absent']);
        Attendance::query()->create(['event_id' => $sessions[1]->id, 'user_id' => $student->id, 'final_status' => 'excused']);
        $this->assertSame('at_risk', CourseCompletion::forUser($student)->first()['status']);

        Attendance::query()->create(['event_id' => $sessions[2]->id, 'user_id' => $student->id, 'final_status' => 'absent']);
        $this->assertSame('failed', CourseCompletion::forUser($student)->first()['status']);

        Carbon::setTestNow('2026-12-01 12:00:00');
        $other = $this->member('Másik');
        $this->enroll($other, $course, 'approved');
        $this->assertSame('completed', CourseCompletion::forUser($other)->first()['status']);
    }

    public function test_a_waiver_needs_every_elnokseg_member_and_the_subject_never_votes(): void
    {
        Notification::fake();
        $portfolio = OrgUnit::query()->create(['semester_id' => $this->semester->id, 'type' => 'portfolio', 'name' => 'P', 'slug' => 'p']);
        $vpOne = $this->member('Alelnök 1');
        $vpTwo = $this->member('Alelnök 2');
        $this->assign($vpOne, 'vice_president', $portfolio->id);
        $this->assign($vpTwo, 'vice_president', $portfolio->id);
        $course = $this->course();
        $student = $this->member('Diák');
        $this->enroll($student, $course, 'approved');

        $this->actingAs($student)->post(route('waivers.store'), ['course_offering_id' => $course->id, 'reason' => 'Kórházban voltam két hétig.'])->assertSessionHasNoErrors();
        $waiver = $student->hasMany(ObligationWaiver::class)->firstOrFail();
        Notification::assertSentTo([$this->president, $vpOne, $vpTwo], FaktNotification::class);

        // A plain member cannot file one for someone else, nor vote.
        $this->actingAs($student)->post(route('waivers.store'), ['user_id' => $vpOne->id, 'course_offering_id' => $course->id, 'reason' => 'Valaki más nevében.'])->assertForbidden();
        $this->confirmed($student)->post(route('waivers.vote', $waiver), ['decision' => 'approve'])->assertForbidden();

        $this->confirmed($this->president)->post(route('waivers.vote', $waiver), ['decision' => 'approve']);
        $this->confirmed($vpOne)->post(route('waivers.vote', $waiver), ['decision' => 'approve']);
        $this->assertSame('pending', $waiver->fresh()->status);
        $this->confirmed($vpTwo)->post(route('waivers.vote', $waiver), ['decision' => 'approve']);
        $this->assertSame('approved', $waiver->fresh()->status);

        foreach ($course->sessions()->get() as $session) {
            Attendance::query()->create(['event_id' => $session->id, 'user_id' => $student->id, 'final_status' => 'absent']);
        }
        $this->assertSame('waived', CourseCompletion::forUser($student)->first()['status']);

        // An Alelnök's own waiver: the other two decide, one "no" rejects it.
        $this->actingAs($this->president)->post(route('waivers.store'), ['user_id' => $vpOne->id, 'course_offering_id' => $course->id, 'reason' => 'Az Alelnök felmentése.'])->assertSessionHasNoErrors();
        $own = $vpOne->hasMany(ObligationWaiver::class)->firstOrFail();
        $this->confirmed($vpOne)->post(route('waivers.vote', $own), ['decision' => 'approve'])->assertForbidden();
        $this->confirmed($vpTwo)->post(route('waivers.vote', $own), ['decision' => 'reject']);
        $this->assertSame('rejected', $own->fresh()->status);
    }

    public function test_the_ktszt_records_attendance_on_course_sessions(): void
    {
        $course = $this->course();
        $student = $this->member('Diák');
        $this->enroll($student, $course, 'approved');
        $session = $course->sessions()->first();

        $this->actingAs($this->ktszt)->patch(route('calendar.finalize', $session), ['user_id' => $student->id, 'final_status' => 'absent'])->assertSessionHasNoErrors();
        $this->assertSame(1, CourseCompletion::forUser($student)->first()['absences']);
        $this->actingAs($student)->patch(route('calendar.finalize', $session), ['user_id' => $student->id, 'final_status' => 'present'])->assertForbidden();
    }

    public function test_the_courses_page_renders_polls_and_placement(): void
    {
        $this->course();
        $this->actingAs($this->ktszt)->get(route('courses.index'))->assertOk()
            ->assertInertia(fn ($page) => $page->component('Courses/Index')->has('placement')->where('courses.0.sessions_count', 4));
    }
}
