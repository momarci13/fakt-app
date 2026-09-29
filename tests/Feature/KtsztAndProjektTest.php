<?php

namespace Tests\Feature;

use App\Models\CourseOffering;
use App\Models\EnrollmentRequest;
use App\Models\OrgUnit;
use App\Models\Project;
use App\Models\RoleAssignment;
use App\Models\Semester;
use App\Models\User;
use App\Support\Ktszt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KtsztAndProjektTest extends TestCase
{
    use RefreshDatabase;

    private Semester $semester;

    private User $president;

    private User $szakmaisagVicePresident;

    private OrgUnit $portfolio;

    private OrgUnit $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->semester = Semester::query()->create([
            'name' => 'Teszt félév', 'starts_at' => now()->subMonth(), 'ends_at' => now()->addMonths(5),
            'is_active' => true, 'course_selection_open' => true,
        ]);

        $this->president = $this->approved('Elnök');
        $this->szakmaisagVicePresident = $this->approved('Szakmaiság Alelnök');

        $this->portfolio = OrgUnit::query()->create(['semester_id' => $this->semester->id, 'type' => 'portfolio', 'name' => 'Szakmaiság', 'slug' => 'szakmaisag-portfolio']);
        $this->team = OrgUnit::query()->create(['semester_id' => $this->semester->id, 'parent_id' => $this->portfolio->id, 'type' => 'team', 'name' => 'Szakmaiság', 'slug' => 'szakmaisag']);

        $this->assign($this->president, 'president');
        $this->assign($this->szakmaisagVicePresident, 'vice_president', $this->portfolio->id);
    }

    private function approved(string $name): User
    {
        return User::factory()->create(['name' => $name, 'approval_status' => 'approved']);
    }

    private function assign(User $user, string $role, ?int $orgUnitId = null): RoleAssignment
    {
        return RoleAssignment::query()->create([
            'semester_id' => $this->semester->id,
            'org_unit_id' => $orgUnitId,
            'user_id' => $user->id,
            'appointed_by' => $this->president->id,
            'role' => $role,
            'starts_at' => now()->subDay(),
            'ends_at' => $this->semester->ends_at,
        ]);
    }

    // ------------------------------------------------------------------ KTSZT

    public function test_the_two_ex_officio_seats_are_derived_from_the_szakmaisag_roles(): void
    {
        $teamLeader = $this->approved('Szakmaiság Teamvezető');
        $this->assign($teamLeader, 'team_leader', $this->team->id);

        $exOfficio = Ktszt::exOfficioUserIds($this->semester->id);

        $this->assertCount(2, $exOfficio);
        $this->assertTrue($exOfficio->contains($this->szakmaisagVicePresident->id));
        $this->assertTrue($exOfficio->contains($teamLeader->id));
    }

    public function test_a_team_leader_outside_szakmaisag_gets_no_seat(): void
    {
        $other = OrgUnit::query()->create(['semester_id' => $this->semester->id, 'type' => 'team', 'name' => 'Közösség', 'slug' => 'kozosseg']);
        $leader = $this->approved('Közösség Teamvezető');
        $this->assign($leader, 'team_leader', $other->id);

        $this->assertFalse(Ktszt::memberUserIds($this->semester->id)->contains($leader->id));
    }

    public function test_an_elected_member_who_becomes_an_officer_loses_the_elected_seat(): void
    {
        // Határozat 4.2
        $elected = $this->approved('Választott tag');
        $this->assign($elected, Ktszt::ROLE);

        $this->assertTrue(Ktszt::electedUserIds($this->semester->id)->contains($elected->id));
        $this->assertSame(2, Ktszt::electedSeatsRemaining($this->semester->id));

        $this->assign($elected, 'team_leader', $this->team->id);

        $this->assertFalse(Ktszt::electedUserIds($this->semester->id)->contains($elected->id));
        $this->assertTrue(Ktszt::exOfficioUserIds($this->semester->id)->contains($elected->id));
        $this->assertSame(3, Ktszt::electedSeatsRemaining($this->semester->id));
    }

    public function test_the_president_can_appoint_three_elected_members_but_not_a_fourth(): void
    {
        $this->withSession(['auth.password_confirmed_at' => time()]);

        foreach (range(1, 3) as $i) {
            $this->actingAs($this->president)
                ->withSession(['auth.password_confirmed_at' => time()])
                ->post(route('admin.ktszt.appoint'), ['user_id' => $this->approved("Tag {$i}")->id])
                ->assertSessionHasNoErrors();
        }

        $this->assertCount(3, Ktszt::electedUserIds($this->semester->id));

        $this->actingAs($this->president)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('admin.ktszt.appoint'), ['user_id' => $this->approved('Negyedik')->id])
            ->assertSessionHasErrors('user_id');

        $this->assertCount(3, Ktszt::electedUserIds($this->semester->id));
    }

    public function test_only_the_president_appoints_elected_members(): void
    {
        $this->actingAs($this->szakmaisagVicePresident)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('admin.ktszt.appoint'), ['user_id' => $this->approved('Jelölt')->id])
            ->assertForbidden();
    }

    public function test_a_ktszt_member_gains_course_authority(): void
    {
        $elected = $this->approved('Választott tag');

        $this->actingAs($elected)
            ->post(route('courses.store'), $this->coursePayload())
            ->assertForbidden();

        $this->assign($elected, Ktszt::ROLE);

        $this->actingAs($elected)
            ->post(route('courses.store'), $this->coursePayload())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('course_offerings', ['title' => 'KTSZT kurzus']);
    }

    public function test_a_ktszt_member_cannot_decide_their_own_enrollment(): void
    {
        // Határozat 1.2.6
        $elected = $this->approved('Választott tag');
        $this->assign($elected, Ktszt::ROLE);

        $course = CourseOffering::query()->create([
            'semester_id' => $this->semester->id, 'created_by' => $this->president->id, 'title' => 'Kurzus',
            'category' => 'Szakmaiság', 'instructor_name' => 'Oktató', 'capacity' => 10,
            'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(),
        ]);

        $own = EnrollmentRequest::query()->create([
            'course_offering_id' => $course->id, 'user_id' => $elected->id, 'status' => 'pending', 'preference_rank' => 1,
        ]);
        $other = EnrollmentRequest::query()->create([
            'course_offering_id' => $course->id, 'user_id' => $this->approved('Másik tag')->id, 'status' => 'pending', 'preference_rank' => 1,
        ]);

        $this->actingAs($elected)
            ->patch(route('courses.review', $own), ['status' => 'approved'])
            ->assertForbidden();

        $this->actingAs($elected)
            ->patch(route('courses.review', $other), ['status' => 'approved'])
            ->assertSessionHasNoErrors();
    }

    /** @return array<string, mixed> */
    private function coursePayload(): array
    {
        return [
            'title' => 'KTSZT kurzus', 'category' => 'Szakmaiság', 'instructor_name' => 'Oktató',
            'capacity' => 10, 'starts_at' => now()->addDays(2)->toDateTimeString(),
            'ends_at' => now()->addDays(2)->addHour()->toDateTimeString(),
        ];
    }

    // ---------------------------------------------------------------- Projekt

    public function test_only_the_president_creates_a_projekt(): void
    {
        $lead = $this->approved('Projektvezető');

        $this->actingAs($this->szakmaisagVicePresident)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('organization.projects.store'), ['name' => 'Tiltott projekt', 'lead_user_id' => $lead->id])
            ->assertForbidden();

        $this->actingAs($this->president)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('organization.projects.store'), ['name' => 'Kutatási projekt', 'lead_user_id' => $lead->id])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('projects', ['name' => 'Kutatási projekt', 'lead_user_id' => $lead->id]);
        $this->assertDatabaseHas('role_assignments', ['user_id' => $lead->id, 'role' => 'project_leader', 'revoked_at' => null]);
    }

    public function test_the_project_leader_manages_members_and_an_outsider_cannot(): void
    {
        $lead = $this->approved('Projektvezető');
        $recruit = $this->approved('Projekttag');
        $outsider = $this->approved('Kívülálló');

        $project = Project::query()->create([
            'semester_id' => $this->semester->id, 'lead_user_id' => $lead->id, 'created_by' => $this->president->id,
            'name' => 'Projekt', 'status' => 'active', 'starts_at' => now()->toDateString(),
        ]);
        $project->members()->sync([$lead->id]);

        $this->actingAs($outsider)
            ->post(route('organization.projects.members.add', $project), ['user_id' => $recruit->id])
            ->assertForbidden();

        $this->actingAs($lead)
            ->post(route('organization.projects.members.add', $project), ['user_id' => $recruit->id])
            ->assertSessionHasNoErrors();

        $this->assertTrue($project->fresh()->members->contains($recruit->id));

        // A Projekt tisztség has to be countable for SZMSZ 8.4.
        $this->assertDatabaseHas('role_assignments', [
            'user_id' => $recruit->id, 'role' => 'project_member', 'semester_id' => $this->semester->id, 'revoked_at' => null,
        ]);
    }

    public function test_removing_a_member_closes_their_projekt_tisztseg(): void
    {
        $lead = $this->approved('Projektvezető');
        $recruit = $this->approved('Projekttag');

        $project = Project::query()->create([
            'semester_id' => $this->semester->id, 'lead_user_id' => $lead->id, 'created_by' => $this->president->id,
            'name' => 'Projekt', 'status' => 'active', 'starts_at' => now()->toDateString(),
        ]);
        $project->members()->sync([$lead->id]);

        $this->actingAs($lead)->post(route('organization.projects.members.add', $project), ['user_id' => $recruit->id]);
        $this->actingAs($lead)->delete(route('organization.projects.members.remove', [$project, $recruit]));

        $this->assertFalse($project->fresh()->members->contains($recruit->id));
        $this->assertNotNull(
            RoleAssignment::query()->where('user_id', $recruit->id)->where('role', 'project_member')->first()->revoked_at
        );
    }

    public function test_the_project_leader_cannot_be_removed_as_a_member(): void
    {
        $lead = $this->approved('Projektvezető');

        $project = Project::query()->create([
            'semester_id' => $this->semester->id, 'lead_user_id' => $lead->id, 'created_by' => $this->president->id,
            'name' => 'Projekt', 'status' => 'active', 'starts_at' => now()->toDateString(),
        ]);
        $project->members()->sync([$lead->id]);

        $this->actingAs($lead)
            ->delete(route('organization.projects.members.remove', [$project, $lead]))
            ->assertStatus(422);
    }

    public function test_projekt_membership_does_not_grant_a_ktszt_seat(): void
    {
        $lead = $this->approved('Projektvezető');
        $this->assign($lead, 'project_leader');

        $this->assertFalse(Ktszt::memberUserIds($this->semester->id)->contains($lead->id));
    }
}
