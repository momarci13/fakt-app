<?php

namespace Tests\Feature;

use App\Models\OrgUnit;
use App\Models\RoleAssignment;
use App\Models\Semester;
use App\Models\TeamMembership;
use App\Models\User;
use App\Support\Mandate;
use App\Support\OrgStructure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Activating a semester must never lock the Elnök out, and mandates follow
 * their statutory periods: Elnök/Alelnök 1 July – 30 June, Teamvezető per
 * half-year.
 */
class SemesterRolloverTest extends TestCase
{
    use RefreshDatabase;

    private Semester $autumn;

    private User $president;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-12-20 10:00:00');

        $this->autumn = Semester::query()->create(['name' => '2026 ősz', 'starts_at' => '2026-07-01', 'ends_at' => '2026-12-31', 'is_active' => true]);
        OrgStructure::ensureFor($this->autumn);
        $this->president = User::factory()->create(['name' => 'Elnök', 'approval_status' => 'approved']);
        $this->assign($this->president, 'president', null, '2026-07-01', '2027-06-30');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function assign(User $user, string $role, ?int $unitId, string $start, ?string $end, ?Semester $semester = null): RoleAssignment
    {
        return RoleAssignment::query()->create([
            'semester_id' => ($semester ?? $this->autumn)->id, 'org_unit_id' => $unitId, 'user_id' => $user->id,
            'role' => $role, 'starts_at' => $start, 'ends_at' => $end,
        ]);
    }

    private function unit(string $slug, ?Semester $semester = null): OrgUnit
    {
        return OrgUnit::query()->where('semester_id', ($semester ?? $this->autumn)->id)->where('slug', $slug)->firstOrFail();
    }

    private function asPresident(): static
    {
        return $this->actingAs($this->president)->withSession(['auth.password_confirmed_at' => now()->unix()]);
    }

    public function test_mandate_periods(): void
    {
        $this->assertSame('2027-06-30', Mandate::endFor('president', Carbon::parse('2026-09-30'))->toDateString());
        $this->assertSame('2026-06-30', Mandate::endFor('vice_president', Carbon::parse('2026-02-10'))->toDateString());
        $this->assertSame('2026-12-31', Mandate::endFor('team_leader', Carbon::parse('2026-07-01'))->toDateString());
        $this->assertSame('2027-06-30', Mandate::endFor('team_leader', Carbon::parse('2027-01-01'))->toDateString());
        $this->assertNull(Mandate::endFor('project_member', Carbon::parse('2027-01-01')));
    }

    public function test_spring_activation_carries_elnok_and_alelnok_but_not_teamvezeto(): void
    {
        $vp = User::factory()->create(['approval_status' => 'approved']);
        $tl = User::factory()->create(['approval_status' => 'approved']);
        $member = User::factory()->create(['approval_status' => 'approved']);
        $this->assign($vp, 'vice_president', $this->unit('szakmaisag-portfolio')->id, '2026-07-01', '2027-06-30');
        $this->assign($tl, 'team_leader', $this->unit('szakmaisag')->id, '2026-07-01', '2026-12-31');
        TeamMembership::query()->create(['semester_id' => $this->autumn->id, 'org_unit_id' => $this->unit('szakmaisag')->id, 'user_id' => $member->id, 'starts_at' => '2026-07-01', 'ends_at' => '2026-12-31']);

        $this->asPresident()->post(route('admin.semesters.store'), [
            'name' => '2027 tavasz', 'starts_at' => '2027-01-01', 'ends_at' => '2027-06-30',
            'activate' => true, 'carry_teams' => true,
        ])->assertSessionHasNoErrors();

        $spring = Semester::query()->where('name', '2027 tavasz')->firstOrFail();
        $this->assertTrue($spring->fresh()->is_active);
        $this->assertTrue($this->president->fresh()->isPresident());
        $this->asPresident()->get(route('admin.index'))->assertOk();

        $this->assertDatabaseHas('role_assignments', ['semester_id' => $spring->id, 'user_id' => $vp->id, 'role' => 'vice_president', 'org_unit_id' => $this->unit('szakmaisag-portfolio', $spring)->id]);
        $this->assertDatabaseMissing('role_assignments', ['semester_id' => $spring->id, 'user_id' => $tl->id, 'role' => 'team_leader']);
        $this->assertDatabaseHas('team_memberships', ['semester_id' => $spring->id, 'user_id' => $member->id, 'org_unit_id' => $this->unit('szakmaisag', $spring)->id]);
        // Activated on 20 December for a 1 January start: no gap.
        $this->assertSame('2026-12-20', RoleAssignment::query()->where('semester_id', $spring->id)->where('role', 'president')->first()->starts_at->toDateString());
    }

    public function test_july_activation_is_refused_without_a_new_elnok(): void
    {
        Carbon::setTestNow('2027-06-25 10:00:00');
        $spring = Semester::query()->create(['name' => '2027 tavasz', 'starts_at' => '2027-01-01', 'ends_at' => '2027-06-30']);
        $this->asPresident()->post(route('admin.semesters.activate', $spring))->assertSessionHasNoErrors();

        $this->asPresident()->post(route('admin.semesters.store'), [
            'name' => '2027 ősz', 'starts_at' => '2027-07-01', 'ends_at' => '2027-12-31', 'activate' => true,
        ])->assertSessionHasErrors('semester');

        $next = Semester::query()->where('name', '2027 ősz')->firstOrFail();
        $this->assertFalse($next->fresh()->is_active);
        $this->assertTrue($spring->fresh()->is_active);
        $this->assertTrue($this->president->fresh()->isPresident());
        $this->assertSame(0, RoleAssignment::query()->where('semester_id', $next->id)->count());
    }

    public function test_the_outgoing_elnok_appoints_the_next_one_and_hands_over(): void
    {
        Carbon::setTestNow('2027-06-25 10:00:00');
        $successor = User::factory()->create(['approval_status' => 'approved']);
        $next = Semester::query()->create(['name' => '2027 ősz', 'starts_at' => '2027-07-01', 'ends_at' => '2027-12-31']);

        $this->asPresident()->post(route('admin.semesters.president', $next), ['user_id' => $successor->id])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('role_assignments', ['semester_id' => $next->id, 'user_id' => $successor->id, 'role' => 'president', 'ends_at' => '2028-06-30 00:00:00']);

        $this->asPresident()->post(route('admin.semesters.activate', $next))->assertSessionHasNoErrors();

        $this->assertTrue($next->fresh()->is_active);
        $this->assertTrue($successor->fresh()->isPresident());
        $this->assertFalse($this->president->fresh()->isPresident());
    }

    public function test_only_the_elnok_manages_semesters(): void
    {
        $member = User::factory()->create(['approval_status' => 'approved']);
        $next = Semester::query()->create(['name' => 'X', 'starts_at' => '2027-01-01', 'ends_at' => '2027-06-30']);

        $this->actingAs($member)->withSession(['auth.password_confirmed_at' => now()->unix()])
            ->post(route('admin.semesters.activate', $next))->assertForbidden();
        $this->actingAs($member)->withSession(['auth.password_confirmed_at' => now()->unix()])
            ->post(route('admin.semesters.president', $next), ['user_id' => $member->id])->assertForbidden();
    }

    public function test_bootstrap_recovers_an_active_semester_without_elnok(): void
    {
        // Simulates the old lockout: the active semester has no Elnök while
        // an older one still does.
        $orphan = Semester::query()->create(['name' => 'Árva', 'starts_at' => '2027-01-01', 'ends_at' => '2027-06-30']);
        Semester::query()->update(['is_active' => false]);
        $orphan->update(['is_active' => true]);

        $this->artisan('fakt:bootstrap-president', ['email' => 'uj-elnok@example.test'])->assertSuccessful();

        $this->assertTrue(User::query()->where('email', 'uj-elnok@example.test')->firstOrFail()->isPresident());
    }
}
