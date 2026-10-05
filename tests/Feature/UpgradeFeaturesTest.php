<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\MemberProfile;
use App\Models\OrgUnit;
use App\Models\RoleAssignment;
use App\Models\Semester;
use App\Models\Task;
use App\Models\TeamMembership;
use App\Models\User;
use App\Notifications\DailyDigest;
use App\Notifications\FaktNotification;
use App\Support\EventCheckIn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * The eight 2026/27 features: month grid data, per-event .ics, daily digest,
 * QR check-in, leader overview, system health, CSV exports, member directory.
 */
class UpgradeFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private Semester $semester;

    private User $president;

    private User $leader;

    private User $member;

    private OrgUnit $team;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-05 18:10:00');

        $this->semester = Semester::query()->create(['name' => 'S', 'starts_at' => '2026-07-01', 'ends_at' => '2026-12-31', 'is_active' => true]);
        $portfolio = OrgUnit::query()->create(['semester_id' => $this->semester->id, 'type' => 'portfolio', 'name' => 'Portfólió', 'slug' => 'p']);
        $this->team = OrgUnit::query()->create(['semester_id' => $this->semester->id, 'type' => 'team', 'name' => 'Marketing', 'slug' => 'marketing', 'parent_id' => $portfolio->id]);
        $this->president = $this->user('Elnök');
        $this->leader = $this->user('Teamvezető');
        $this->member = $this->user('Tag Anna');
        $this->role($this->president, 'president');
        $this->role($this->leader, 'team_leader', $this->team->id);
        TeamMembership::query()->create(['semester_id' => $this->semester->id, 'org_unit_id' => $this->team->id, 'user_id' => $this->member->id, 'starts_at' => '2026-07-01']);
        $this->event = Event::query()->create(['semester_id' => $this->semester->id, 'org_unit_id' => $this->team->id, 'organizer_id' => $this->leader->id, 'title' => 'Teamgyűlés', 'type' => 'team', 'starts_at' => '2026-10-05 18:00:00', 'ends_at' => '2026-10-05 19:00:00', 'visibility' => 'scope', 'obligation' => 'required']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function user(string $name): User
    {
        $user = User::factory()->create(['name' => $name, 'approval_status' => 'approved']);
        MemberProfile::query()->create(['user_id' => $user->id, 'member_status' => 'active', 'expertise' => 'Pénzügy']);

        return $user;
    }

    private function role(User $user, string $role, ?int $unit = null): void
    {
        RoleAssignment::query()->create(['semester_id' => $this->semester->id, 'org_unit_id' => $unit, 'user_id' => $user->id, 'role' => $role, 'starts_at' => '2026-07-01']);
    }

    public function test_calendar_events_carry_manage_flags_for_the_grid_and_links(): void
    {
        $this->actingAs($this->leader)->get(route('calendar.index'))->assertInertia(fn (Assert $page) => $page
            ->where('events.0.can_manage', true)->where('events.0.status', 'scheduled'));
        $this->actingAs($this->member)->get(route('calendar.index'))->assertInertia(fn (Assert $page) => $page
            ->where('events.0.can_manage', false));
    }

    public function test_single_event_ics_download_respects_visibility(): void
    {
        $this->actingAs($this->member)->get(route('calendar.events.download', $this->event))
            ->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=utf-8')->assertSee('SUMMARY:Teamgyűlés', false);
        $this->actingAs($this->user('Kívülálló'))->get(route('calendar.events.download', $this->event))->assertNotFound();
    }

    public function test_qr_check_in_marks_present_and_rejects_stale_codes(): void
    {
        $this->actingAs($this->member)->get(route('calendar.checkin.screen', $this->event))->assertForbidden();
        $this->actingAs($this->leader)->get(route('calendar.checkin.screen', $this->event))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('Calendar/CheckIn')->where('open', true)->where('qrSvg', fn ($svg) => str_contains($svg, '<svg')));

        $code = EventCheckIn::code($this->event->fresh());
        $this->actingAs($this->member)->get(route('calendar.checkin.show', [$this->event, $code]))
            ->assertInertia(fn (Assert $page) => $page->component('Calendar/CheckInConfirm')->where('valid', true));
        $this->actingAs($this->member)->post(route('calendar.checkin.store', [$this->event, $code]))->assertRedirect('/naptar');

        $attendance = Attendance::query()->where('event_id', $this->event->id)->where('user_id', $this->member->id)->firstOrFail();
        $this->assertSame('present', $attendance->final_status);
        $this->assertNotNull($attendance->checked_in_at);

        // Two minutes later the old code no longer works.
        Carbon::setTestNow('2026-10-05 18:13:00');
        $other = $this->user('Késő');
        TeamMembership::query()->create(['semester_id' => $this->semester->id, 'org_unit_id' => $this->team->id, 'user_id' => $other->id, 'starts_at' => '2026-07-01']);
        $this->actingAs($other)->post(route('calendar.checkin.store', [$this->event, $code]))->assertSessionHas('error');
        $this->assertDatabaseMissing('attendances', ['event_id' => $this->event->id, 'user_id' => $other->id]);

        // Someone the event isn't for can't check in even with a fresh code.
        $this->actingAs($this->user('Idegen'))->post(route('calendar.checkin.store', [$this->event, EventCheckIn::code($this->event->fresh())]))->assertNotFound();
    }

    public function test_digest_members_get_one_daily_email_and_urgent_mail_goes_at_once(): void
    {
        $this->member->forceFill(['notification_mode' => 'digest'])->save();
        $this->leader->forceFill(['notification_mode' => 'immediate'])->save();

        $this->assertSame(['database'], (new FaktNotification('A', 'B'))->via($this->member->fresh()));
        $this->assertSame(['database', 'mail'], (new FaktNotification('A', 'B'))->via($this->leader->fresh()));
        $this->assertSame(['database', 'mail'], (new FaktNotification('A', 'B', '/', true))->via($this->member->fresh()));

        Notification::fake();
        // One unread in-app notification since the last digest.
        $this->member->notifications()->create(['id' => (string) Str::uuid(), 'type' => FaktNotification::class, 'data' => ['title' => 'Új feladat', 'message' => 'Plakát', 'url' => '/']]);

        $this->artisan('fakt:daily-digest')->assertSuccessful();
        Notification::assertSentTo($this->member, DailyDigest::class, fn (DailyDigest $digest) => count($digest->items) === 1);
        Notification::assertNotSentTo($this->leader, DailyDigest::class);

        // Nothing new since the last digest: no second email.
        Notification::fake();
        $this->artisan('fakt:daily-digest')->assertSuccessful();
        Notification::assertNothingSent();
    }

    public function test_members_choose_their_notification_mode(): void
    {
        $this->actingAs($this->member)->patch(route('notifications.preferences'), ['notification_mode' => 'immediate'])->assertSessionHasNoErrors();
        $this->assertSame('immediate', $this->member->fresh()->notification_mode);
        $this->actingAs($this->member)->patch(route('notifications.preferences'), ['notification_mode' => 'hourly'])->assertSessionHasErrors('notification_mode');
    }

    public function test_leader_overview_shows_team_members_with_figures(): void
    {
        $task = Task::query()->create(['semester_id' => $this->semester->id, 'org_unit_id' => $this->team->id, 'created_by' => $this->leader->id, 'title' => 'Lejárt', 'due_at' => '2026-10-01 10:00:00']);
        $task->assignees()->sync([$this->member->id]);
        Attendance::query()->create(['event_id' => $this->event->id, 'user_id' => $this->member->id, 'final_status' => 'present']);

        $this->actingAs($this->member)->get(route('leader.index'))->assertForbidden();
        $this->actingAs($this->leader)->get(route('leader.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Leader/Index')
            ->where('groups.0.name', 'Marketing')
            ->where('groups.0.members.0.name', 'Tag Anna')
            ->where('groups.0.members.0.open_tasks', 1)
            ->where('groups.0.members.0.overdue_tasks', 1)
            ->where('groups.0.members.0.attendance_rate', 100));
    }

    public function test_member_directory_lists_roles_and_hides_private_alumni(): void
    {
        $alumnus = $this->user('Rejtett Alumnus');
        $alumnus->profile->update(['member_status' => 'alumni', 'alumni_visible' => false]);

        $this->actingAs($this->member)->get(route('members.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Members/Index')
            ->where('members', fn ($members) => collect($members)->contains(fn ($m) => $m['name'] === 'Teamvezető' && in_array('Teamvezető · Marketing', $m['roles'], true))
                && ! collect($members)->contains('name', 'Rejtett Alumnus')));
    }

    public function test_system_page_is_for_the_elnok_and_shows_no_secrets(): void
    {
        config(['mail.mailers.smtp.password' => 'TitkosJelszo123']);
        $this->actingAs($this->member)->get(route('admin.system'))->assertForbidden();
        $response = $this->actingAs($this->president)->get(route('admin.system'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/System')->has('checks.pending_migrations')->has('checks.scheduler_ok'));
        $this->assertStringNotContainsString('TitkosJelszo123', $response->getContent());

        Notification::fake();
        $this->actingAs($this->president)->post(route('admin.system.mail'))->assertSessionHasNoErrors();
        Notification::assertSentTo($this->president, FaktNotification::class, fn ($n) => $n->urgent);
    }

    public function test_csv_exports_are_excel_friendly_and_guarded(): void
    {
        $sheet = $this->actingAs($this->leader)->get(route('calendar.events.attendance', $this->event))->assertOk();
        $this->assertStringStartsWith("\xEF\xBB\xBFNév;Email", $sheet->streamedContent());
        $this->assertStringContainsString('Tag Anna', $sheet->streamedContent());
        $this->actingAs($this->member)->get(route('calendar.events.attendance', $this->event))->assertForbidden();

        $this->member->update(['name' => '=HYPERLINK("x")']);
        $members = $this->actingAs($this->president)->withSession(['auth.password_confirmed_at' => now()->unix()])->get(route('admin.export.members'))->assertOk();
        $this->assertStringContainsString("'=HYPERLINK", $members->streamedContent());
        $this->actingAs($this->member)->withSession(['auth.password_confirmed_at' => now()->unix()])->get(route('admin.export.members'))->assertForbidden();
    }
}
