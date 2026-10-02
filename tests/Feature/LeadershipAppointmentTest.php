<?php

namespace Tests\Feature;

use App\Models\OrgUnit;
use App\Models\RoleAssignment;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * SZMSZ 12.2-12.3: the Elnök appoints the Alelnökök; the Elnök or the
 * portfolio's Alelnök appoints the Teamvezetők. One holder per unit.
 */
class LeadershipAppointmentTest extends TestCase
{
    use RefreshDatabase;

    private Semester $semester;

    private User $president;

    private OrgUnit $portfolio;

    private OrgUnit $team;

    private OrgUnit $otherPortfolio;

    private OrgUnit $otherTeam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->semester = Semester::query()->create([
            'name' => 'Teszt félév', 'starts_at' => now()->subMonth(), 'ends_at' => now()->addMonths(5),
            'is_active' => true, 'course_selection_open' => false,
        ]);
        $this->president = $this->approved('Elnök');
        $this->assign($this->president, 'president');

        $this->portfolio = $this->unit('portfolio', 'Szakmaiság', 'szakmaisag-portfolio');
        $this->team = $this->unit('team', 'Szakmaiság', 'szakmaisag', $this->portfolio->id);
        $this->otherPortfolio = $this->unit('portfolio', 'Pénzügy', 'penzugy-vallalati');
        $this->otherTeam = $this->unit('team', 'Pénzügy', 'penzugy-vallalati-kapcsolatok', $this->otherPortfolio->id);
    }

    private function approved(string $name): User
    {
        return User::factory()->create(['name' => $name, 'approval_status' => 'approved']);
    }

    private function unit(string $type, string $name, string $slug, ?int $parentId = null): OrgUnit
    {
        return OrgUnit::query()->create([
            'semester_id' => $this->semester->id, 'parent_id' => $parentId,
            'type' => $type, 'name' => $name, 'slug' => $slug,
        ]);
    }

    private function assign(User $user, string $role, ?int $orgUnitId = null): RoleAssignment
    {
        return RoleAssignment::query()->create([
            'semester_id' => $this->semester->id, 'org_unit_id' => $orgUnitId, 'user_id' => $user->id,
            'appointed_by' => $this->president->id ?? null, 'role' => $role,
            'starts_at' => now()->subDay()->toDateString(), 'ends_at' => $this->semester->ends_at,
        ]);
    }

    private function appointAs(User $actor, User $user, OrgUnit $unit, string $role)
    {
        return $this->actingAs($actor)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->from(route('organization.index'))
            ->post(route('organization.appoint'), [
                'user_id' => $user->id, 'org_unit_id' => $unit->id, 'role' => $role,
            ]);
    }

    private function holders(OrgUnit $unit, string $role): array
    {
        return RoleAssignment::query()
            ->where('org_unit_id', $unit->id)->where('role', $role)->whereNull('revoked_at')
            ->pluck('user_id')->all();
    }

    public function test_the_president_appoints_an_alelnok_and_a_teamvezeto(): void
    {
        $vicePresident = $this->approved('Alelnök');
        $teamLeader = $this->approved('Teamvezető');

        $this->appointAs($this->president, $vicePresident, $this->portfolio, 'vice_president')
            ->assertRedirect(route('organization.index'))
            ->assertSessionHasNoErrors();
        $this->appointAs($this->president, $teamLeader, $this->team, 'team_leader')
            ->assertSessionHasNoErrors();

        $this->assertSame([$vicePresident->id], $this->holders($this->portfolio, 'vice_president'));
        $this->assertSame([$teamLeader->id], $this->holders($this->team, 'team_leader'));
        $this->assertTrue($vicePresident->fresh()->managedOrgUnitIds()->contains($this->team->id));
    }

    public function test_an_alelnok_appoints_a_teamvezeto_only_in_their_own_portfolio(): void
    {
        $vicePresident = $this->approved('Alelnök');
        $this->assign($vicePresident, 'vice_president', $this->portfolio->id);

        $this->appointAs($vicePresident, $this->approved('Saját'), $this->team, 'team_leader')
            ->assertSessionHasNoErrors();
        $this->appointAs($vicePresident, $this->approved('Idegen'), $this->otherTeam, 'team_leader')
            ->assertForbidden();
    }

    public function test_only_the_president_appoints_an_alelnok(): void
    {
        $vicePresident = $this->approved('Alelnök');
        $this->assign($vicePresident, 'vice_president', $this->portfolio->id);

        $this->appointAs($vicePresident, $this->approved('Jelölt'), $this->otherPortfolio, 'vice_president')
            ->assertForbidden();
    }

    public function test_a_teamvezeto_cannot_appoint_or_revoke_a_teamvezeto(): void
    {
        $teamLeader = $this->approved('Teamvezető');
        $assignment = $this->assign($teamLeader, 'team_leader', $this->team->id);

        $this->appointAs($teamLeader, $this->approved('Utód'), $this->team, 'team_leader')
            ->assertForbidden();

        $this->actingAs($teamLeader)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->patch(route('organization.revoke', $assignment))
            ->assertForbidden();
        $this->assertNull($assignment->fresh()->revoked_at);
    }

    public function test_a_unit_keeps_one_holder_until_the_current_one_is_revoked(): void
    {
        $first = $this->approved('Első');
        $current = $this->assign($first, 'vice_president', $this->portfolio->id);
        $second = $this->approved('Második');

        $this->appointAs($this->president, $second, $this->portfolio, 'vice_president')
            ->assertSessionHasErrors('user_id');
        $this->assertSame([$first->id], $this->holders($this->portfolio, 'vice_president'));

        $this->actingAs($this->president)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->patch(route('organization.revoke', $current))
            ->assertSessionHasNoErrors();
        $this->appointAs($this->president, $second, $this->portfolio, 'vice_president')
            ->assertSessionHasNoErrors();

        $this->assertSame([$second->id], $this->holders($this->portfolio, 'vice_president'));
    }

    public function test_a_role_must_match_its_unit_type(): void
    {
        $this->appointAs($this->president, $this->approved('Jelölt'), $this->team, 'vice_president')
            ->assertStatus(422);
    }

    public function test_appointing_asks_for_the_password_and_returns_to_the_page(): void
    {
        $this->actingAs($this->president)
            ->from(route('organization.index'))
            ->post(route('organization.appoint'), [
                'user_id' => $this->approved('Jelölt')->id, 'org_unit_id' => $this->portfolio->id, 'role' => 'vice_president',
            ])
            ->assertRedirect(route('password.confirm'));

        $this->assertSame(route('organization.index'), session('url.intended'));
        $this->assertSame([], $this->holders($this->portfolio, 'vice_president'));
    }

    public function test_the_page_tells_each_leader_which_teamvezeto_they_may_appoint(): void
    {
        $vicePresident = $this->approved('Alelnök');
        $this->assign($vicePresident, 'vice_president', $this->portfolio->id);
        $teamLeader = $this->approved('Teamvezető');
        $this->assign($teamLeader, 'team_leader', $this->team->id);

        $this->actingAs($this->president)->get(route('organization.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('canAdmin', true)
                ->where('appointableTeamIds', [$this->team->id, $this->otherTeam->id]));

        $this->actingAs($vicePresident)->get(route('organization.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('canAdmin', false)
                ->where('appointableTeamIds', [$this->team->id]));

        $this->actingAs($teamLeader)->get(route('organization.index'))
            ->assertInertia(fn (Assert $page) => $page->where('appointableTeamIds', []));
    }
}
