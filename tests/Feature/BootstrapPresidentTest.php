<?php

namespace Tests\Feature;

use App\Models\RoleAssignment;
use App\Models\Semester;
use App\Models\User;
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
}
