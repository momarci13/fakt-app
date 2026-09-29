<?php

namespace Tests\Feature;

use App\Models\OrgUnit;
use App\Models\RoleAssignment;
use App\Models\Semester;
use App\Models\User;
use App\Support\Ktszt;
use App\Support\OrgStructure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BootstrapPresidentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_first_president_and_semester(): void
    {
        $this->artisan('fakt:bootstrap-president', [
            'email' => 'elnok@example.test',
            '--name' => 'Teszt Elnök',
        ])->assertSuccessful();

        $user = User::query()->where('email', 'elnok@example.test')->firstOrFail();

        $this->assertSame('Teszt Elnök', $user->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->profile);
        $this->assertNotNull(Semester::active());
        $this->assertDatabaseHas('role_assignments', [
            'user_id' => $user->id,
            'role' => 'president',
        ]);
    }

    public function test_it_refuses_to_create_a_second_active_president(): void
    {
        $semester = Semester::query()->create([
            'name' => 'Teszt félév',
            'starts_at' => now(),
            'ends_at' => now()->addMonths(6),
            'is_active' => true,
        ]);
        $user = User::factory()->create();
        RoleAssignment::query()->create([
            'semester_id' => $semester->id,
            'user_id' => $user->id,
            'role' => 'president',
            'starts_at' => now(),
        ]);

        $this->artisan('fakt:bootstrap-president', [
            'email' => 'masodik@example.test',
        ])->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'masodik@example.test']);
    }

    public function test_it_creates_the_statutory_org_structure_on_a_fresh_database(): void
    {
        $this->artisan('fakt:bootstrap-president', ['email' => 'elnok@example.test'])->assertSuccessful();

        $semester = Semester::active();

        $this->assertSame(4, OrgUnit::query()->where('semester_id', $semester->id)->where('type', 'portfolio')->count());
        $this->assertSame(6, OrgUnit::query()->where('semester_id', $semester->id)->where('type', 'team')->count());

        // Every Team hangs under a portfolio.
        $this->assertSame(0, OrgUnit::query()->where('type', 'team')->whereNull('parent_id')->count());
    }

    public function test_the_created_szakmaisag_units_give_the_ktszt_its_ex_officio_seats(): void
    {
        $this->artisan('fakt:bootstrap-president', ['email' => 'elnok@example.test'])->assertSuccessful();
        $semester = Semester::active();
        $portfolio = OrgUnit::query()->where('semester_id', $semester->id)->where('slug', 'szakmaisag-portfolio')->firstOrFail();

        $vicePresident = User::factory()->create(['approval_status' => 'approved']);
        RoleAssignment::query()->create([
            'semester_id' => $semester->id, 'org_unit_id' => $portfolio->id, 'user_id' => $vicePresident->id,
            'role' => 'vice_president', 'starts_at' => now()->subDay(),
        ]);

        $this->assertTrue(Ktszt::exOfficioUserIds($semester->id)->contains($vicePresident->id));
    }

    public function test_the_org_structure_is_idempotent(): void
    {
        $semester = Semester::query()->create(['name' => 'Félév', 'starts_at' => now(), 'ends_at' => now()->addMonths(5), 'is_active' => true]);

        $this->assertSame(10, OrgStructure::ensureFor($semester));
        $this->assertSame(0, OrgStructure::ensureFor($semester));
        $this->assertSame(10, OrgUnit::query()->where('semester_id', $semester->id)->count());
    }

    public function test_a_newly_opened_semester_gets_the_structure_too(): void
    {
        $this->artisan('fakt:bootstrap-president', ['email' => 'elnok@example.test'])->assertSuccessful();
        $president = User::query()->where('email', 'elnok@example.test')->firstOrFail();

        $this->actingAs($president)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('admin.semesters.store'), [
                'name' => '2027 tavasz', 'starts_at' => now()->addMonths(5)->toDateString(),
                'ends_at' => now()->addMonths(10)->toDateString(),
            ])
            ->assertSessionHasNoErrors();

        $next = Semester::query()->where('name', '2027 tavasz')->firstOrFail();
        $this->assertSame(10, OrgUnit::query()->where('semester_id', $next->id)->count());
    }
}
