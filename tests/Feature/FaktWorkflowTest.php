<?php

namespace Tests\Feature;

use App\Models\CourseOffering;
use App\Models\Document;
use App\Models\EnrollmentRequest;
use App\Models\Event;
use App\Models\ImportBatch;
use App\Models\MemberProfile;
use App\Models\ObligationRule;
use App\Models\OrgUnit;
use App\Models\ProgressRecord;
use App\Models\RoleAssignment;
use App\Models\Semester;
use App\Models\Task;
use App\Models\TeamMembership;
use App\Models\User;
use App\Support\LifecycleProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class FaktWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Semester $semester;

    private User $president;

    private User $vicePresident;

    private User $member;

    private OrgUnit $portfolio;

    private OrgUnit $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->semester = Semester::query()->create(['name' => 'Teszt félév', 'starts_at' => now()->subMonth(), 'ends_at' => now()->addMonths(5), 'is_active' => true, 'course_selection_open' => true]);
        $this->president = $this->member('Elnök');
        $this->vicePresident = $this->member('Alelnök');
        $this->member = $this->member('Tag');
        $this->portfolio = OrgUnit::query()->create(['semester_id' => $this->semester->id, 'type' => 'portfolio', 'name' => 'Szakmaiság', 'slug' => 'szakmaisag-portfolio']);
        $this->team = OrgUnit::query()->create(['semester_id' => $this->semester->id, 'parent_id' => $this->portfolio->id, 'type' => 'team', 'name' => 'Szakmaiság', 'slug' => 'szakmaisag']);
        RoleAssignment::query()->create(['semester_id' => $this->semester->id, 'user_id' => $this->president->id, 'role' => 'president', 'starts_at' => now()->subMonth(), 'ends_at' => $this->semester->ends_at]);
        RoleAssignment::query()->create(['semester_id' => $this->semester->id, 'org_unit_id' => $this->portfolio->id, 'user_id' => $this->vicePresident->id, 'appointed_by' => $this->president->id, 'role' => 'vice_president', 'starts_at' => now()->subMonth(), 'ends_at' => $this->semester->ends_at]);
    }

    public function test_public_registration_is_available_for_approval_based_access(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_task_delegation_follows_the_leadership_chain(): void
    {
        $teamLeader = $this->member('Teamvezető');
        $projectLeader = $this->member('Projektvezető');
        $projectMember = $this->member('Projekttag');
        RoleAssignment::query()->create([
            'semester_id' => $this->semester->id,
            'org_unit_id' => $this->team->id,
            'user_id' => $teamLeader->id,
            'appointed_by' => $this->vicePresident->id,
            'role' => 'team_leader',
            'starts_at' => now()->subDay(),
            'ends_at' => $this->semester->ends_at,
        ]);
        TeamMembership::query()->create([
            'semester_id' => $this->semester->id,
            'org_unit_id' => $this->team->id,
            'user_id' => $this->member->id,
            'assigned_by' => $teamLeader->id,
            'starts_at' => now()->subDay(),
            'ends_at' => $this->semester->ends_at,
        ]);
        $project = \App\Models\Project::query()->create([
            'semester_id' => $this->semester->id,
            'lead_user_id' => $projectLeader->id,
            'created_by' => $this->president->id,
            'name' => 'Delegálási tesztprojekt',
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => $this->semester->ends_at,
        ]);
        $project->members()->sync([$projectLeader->id, $projectMember->id]);
        RoleAssignment::query()->create([
            'semester_id' => $this->semester->id,
            'user_id' => $projectLeader->id,
            'appointed_by' => $this->president->id,
            'role' => 'project_leader',
            'starts_at' => now()->subDay(),
            'ends_at' => $this->semester->ends_at,
        ]);

        $payload = fn (User $assignee, string $title) => [
            'title' => $title,
            'priority' => 'normal',
            'assignee_ids' => [$assignee->id],
        ];

        $this->actingAs($this->president)
            ->post(route('tasks.store'), $payload($this->vicePresident, 'Elnök az alelnöknek'))
            ->assertRedirect();
        $this->actingAs($this->president)
            ->post(route('tasks.store'), $payload($projectLeader, 'Elnök a projektvezetőnek'))
            ->assertRedirect();
        $this->actingAs($this->president)
            ->post(route('tasks.store'), $payload($this->member, 'Tiltott közvetlen kiosztás'))
            ->assertForbidden();

        $this->actingAs($this->vicePresident)
            ->post(route('tasks.store'), $payload($teamLeader, 'Alelnök a Teamvezetőnek'))
            ->assertRedirect();
        $this->actingAs($this->vicePresident)
            ->post(route('tasks.store'), $payload($this->member, 'Tiltott alelnöki kiosztás'))
            ->assertForbidden();

        $this->actingAs($teamLeader)
            ->post(route('tasks.store'), $payload($this->member, 'Teamvezető a Teamtagnak'))
            ->assertRedirect();
        $this->actingAs($teamLeader)
            ->post(route('tasks.store'), $payload($this->vicePresident, 'Tiltott felfelé delegálás'))
            ->assertForbidden();

        $this->actingAs($projectLeader)
            ->post(route('tasks.store'), $payload($projectMember, 'Projektvezető a projekttagnak'))
            ->assertRedirect();

        $this->assertDatabaseCount('tasks', 5);
    }

    public function test_only_president_can_appoint_a_vice_president(): void
    {
        $candidate = $this->member('Jelölt');
        $payload = ['user_id' => $candidate->id, 'org_unit_id' => $this->portfolio->id, 'role' => 'vice_president'];

        $this->actingAs($this->member)->withSession(['auth.password_confirmed_at' => time()])->post(route('organization.appoint'), $payload)->assertForbidden();
        $this->actingAs($this->president)->withSession(['auth.password_confirmed_at' => time()])->post(route('organization.appoint'), $payload)->assertRedirect();

        $this->assertDatabaseHas('role_assignments', ['user_id' => $candidate->id, 'role' => 'vice_president', 'appointed_by' => $this->president->id]);
        $this->assertDatabaseHas('audit_entries', ['event' => 'appointed', 'actor_id' => $this->president->id]);
    }

    public function test_vice_president_can_assign_a_member_only_inside_their_portfolio(): void
    {
        $otherPortfolio = OrgUnit::query()->create(['semester_id' => $this->semester->id, 'type' => 'portfolio', 'name' => 'Pénzügy', 'slug' => 'penzugy']);
        $otherTeam = OrgUnit::query()->create(['semester_id' => $this->semester->id, 'parent_id' => $otherPortfolio->id, 'type' => 'team', 'name' => 'Pénzügy Team', 'slug' => 'penzugy-team']);

        $this->actingAs($this->vicePresident)->withSession(['auth.password_confirmed_at' => time()])->post(route('organization.members.assign'), ['user_id' => $this->member->id, 'org_unit_id' => $otherTeam->id])->assertForbidden();
        $this->actingAs($this->vicePresident)->withSession(['auth.password_confirmed_at' => time()])->post(route('organization.members.assign'), ['user_id' => $this->member->id, 'org_unit_id' => $this->team->id])->assertRedirect();

        $this->assertDatabaseHas('team_memberships', ['user_id' => $this->member->id, 'org_unit_id' => $this->team->id, 'semester_id' => $this->semester->id]);
    }

    public function test_tasks_are_visible_only_in_the_users_scope(): void
    {
        TeamMembership::query()->create(['semester_id' => $this->semester->id, 'org_unit_id' => $this->team->id, 'user_id' => $this->member->id, 'assigned_by' => $this->vicePresident->id, 'starts_at' => now(), 'ends_at' => $this->semester->ends_at]);
        $scopedTask = Task::query()->create(['semester_id' => $this->semester->id, 'org_unit_id' => $this->team->id, 'created_by' => $this->vicePresident->id, 'title' => 'Team feladat', 'status' => 'todo', 'priority' => 'normal']);
        $privateTask = Task::query()->create(['semester_id' => $this->semester->id, 'created_by' => $this->president->id, 'title' => 'Elnöki feladat', 'status' => 'todo', 'priority' => 'normal']);

        $visible = Task::query()->visibleTo($this->member)->pluck('id');

        $this->assertTrue($visible->contains($scopedTask->id));
        $this->assertFalse($visible->contains($privateTask->id));
        $this->assertCount(2, Task::query()->visibleTo($this->president)->get());
    }

    public function test_course_capacity_cannot_be_overbooked(): void
    {
        $course = CourseOffering::query()->create(['semester_id' => $this->semester->id, 'created_by' => $this->vicePresident->id, 'title' => 'Teszt kurzus', 'category' => 'Szakmaiság', 'instructor_name' => 'Oktató', 'capacity' => 1, 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour()]);
        $first = EnrollmentRequest::query()->create(['course_offering_id' => $course->id, 'user_id' => $this->member->id, 'preference_rank' => 1, 'status' => 'approved']);
        $secondMember = $this->member('Második tag');
        $second = EnrollmentRequest::query()->create(['course_offering_id' => $course->id, 'user_id' => $secondMember->id, 'preference_rank' => 1, 'status' => 'pending']);

        $this->actingAs($this->vicePresident)->patch(route('courses.review', $second), ['status' => 'approved'])->assertSessionHasErrors('status');
        $this->assertSame('pending', $second->refresh()->status);
        $this->assertSame('approved', $first->refresh()->status);
    }

    public function test_course_selection_reports_time_conflicts(): void
    {
        $first = CourseOffering::query()->create(['semester_id' => $this->semester->id, 'created_by' => $this->vicePresident->id, 'title' => 'Első kurzus', 'category' => 'Szakmaiság', 'instructor_name' => 'Oktató', 'capacity' => 20, 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHours(2)]);
        $second = CourseOffering::query()->create(['semester_id' => $this->semester->id, 'created_by' => $this->vicePresident->id, 'title' => 'Ütköző kurzus', 'category' => 'Szakmaiság', 'instructor_name' => 'Oktató', 'capacity' => 20, 'starts_at' => now()->addDay()->addHour(), 'ends_at' => now()->addDay()->addHours(3)]);
        EnrollmentRequest::query()->create(['course_offering_id' => $first->id, 'user_id' => $this->member->id, 'preference_rank' => 1, 'status' => 'pending']);

        $this->actingAs($this->member)->post(route('courses.request', $second), ['preference_rank' => 2])->assertSessionHasErrors('preference_rank');
        $this->assertDatabaseMissing('enrollment_requests', ['course_offering_id' => $second->id, 'user_id' => $this->member->id]);
    }

    public function test_private_calendar_feed_contains_authorized_events_and_task_deadlines(): void
    {
        $token = Str::random(48);
        $this->member->update(['calendar_token' => $token]);
        Event::query()->create(['semester_id' => $this->semester->id, 'organizer_id' => $this->president->id, 'title' => 'Nyitógyűlés', 'type' => 'assembly', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(), 'visibility' => 'company', 'obligation' => 'required']);
        $task = Task::query()->create(['semester_id' => $this->semester->id, 'created_by' => $this->member->id, 'title' => 'Beadandó', 'status' => 'todo', 'priority' => 'high', 'due_at' => now()->addDays(2)]);
        $task->assignees()->attach($this->member->id);

        $this->get(route('calendar.feed', $token))->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=utf-8')->assertSee('Nyitógyűlés')->assertSee('Határidő: Beadandó');
        $this->get(route('calendar.feed', Str::random(48)))->assertNotFound();
    }

    public function test_lifecycle_progress_uses_only_approved_records(): void
    {
        ObligationRule::query()->create(['semester_id' => $this->semester->id, 'code' => 'course_count', 'name' => 'Kurzusok', 'kind' => 'minimum', 'threshold' => 3, 'version' => 1, 'effective_at' => now()]);
        ProgressRecord::query()->create(['user_id' => $this->member->id, 'semester_id' => $this->semester->id, 'type' => 'course_count', 'value' => 2, 'status' => 'approved']);
        ProgressRecord::query()->create(['user_id' => $this->member->id, 'semester_id' => $this->semester->id, 'type' => 'course_count', 'value' => 1, 'status' => 'pending']);

        $progress = LifecycleProgress::for($this->member, $this->semester)->first();

        $this->assertSame(2.0, $progress['current']);
        $this->assertFalse($progress['complete']);
        $this->assertSame(67.0, (float) $progress['percent']);
    }

    public function test_member_import_is_staged_validated_and_applied_without_duplicates(): void
    {
        $csv = "name,email,member_status,cohort_year\nImport Anna,anna.import@example.hu,active,2026\n";

        $this->actingAs($this->president)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('admin.imports.stage'), ['file' => UploadedFile::fake()->createWithContent('tagok.csv', $csv)])
            ->assertRedirect();

        $this->assertDatabaseHas('import_batches', ['status' => 'staged', 'valid_rows' => 1, 'invalid_rows' => 0]);
        $batch = ImportBatch::query()->firstOrFail();

        $this->actingAs($this->president)->withSession(['auth.password_confirmed_at' => time()])->post(route('admin.imports.apply', $batch))->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'anna.import@example.hu']);
        $this->assertDatabaseHas('member_profiles', ['member_status' => 'active', 'cohort_year' => 2026]);
        $this->assertSame('applied', $batch->refresh()->status);

        $this->actingAs($this->president)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('admin.imports.stage'), ['file' => UploadedFile::fake()->createWithContent('ismet.csv', $csv)])
            ->assertRedirect();
        $this->assertDatabaseHas('import_batches', ['invalid_rows' => 1]);
    }

    public function test_private_documents_are_downloaded_only_through_authorized_route(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('documents/guidebook.pdf', 'PDF test');
        $document = Document::query()->create([
            'uploaded_by' => $this->president->id,
            'category' => 'guidebook',
            'original_name' => 'guidebook.pdf',
            'path' => 'documents/guidebook.pdf',
            'mime_type' => 'application/pdf',
            'size' => 8,
            'visibility' => 'members',
        ]);

        $this->get(route('documents.download', $document))->assertRedirect('/login');
        $this->actingAs($this->member)->get(route('documents.download', $document))->assertOk()->assertDownload('guidebook.pdf');
    }

    public function test_recurring_tasks_are_generated_idempotently(): void
    {
        $task = Task::query()->create(['semester_id' => $this->semester->id, 'created_by' => $this->vicePresident->id, 'title' => 'Heti feladat', 'status' => 'done', 'priority' => 'normal', 'due_at' => now()->subDay(), 'recurrence_rule' => 'FREQ=WEEKLY']);
        $task->assignees()->attach($this->member->id);

        $this->artisan('fakt:recurring-tasks')->assertSuccessful();
        $this->artisan('fakt:recurring-tasks')->assertSuccessful();

        $this->assertSame(1, Task::query()->where('parent_id', $task->id)->count());
        $this->assertTrue(Task::query()->where('parent_id', $task->id)->firstOrFail()->assignees->contains($this->member));
    }

    private function member(string $name): User
    {
        $user = User::factory()->create(['name' => $name]);
        MemberProfile::query()->create(['user_id' => $user->id, 'member_status' => 'active']);

        return $user;
    }
}
