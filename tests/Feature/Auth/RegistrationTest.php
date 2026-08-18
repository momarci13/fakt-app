<?php

namespace Tests\Feature\Auth;

use App\Models\MemberProfile;
use App\Models\RoleAssignment;
use App\Models\Semester;
use App\Models\User;
use App\Notifications\FaktNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get(route('register'))->assertOk();
    }

    public function test_new_registration_waits_for_presidential_approval(): void
    {
        Notification::fake();
        $president = $this->president();

        $response = $this->post(route('register.store'), [
            'name' => 'Teszt Jelentkező',
            'email' => 'jelentkezo@example.com',
            'cohort_year' => 2026,
            'registration_note' => 'A FAKT 2026-os évfolyamának aktív tagja vagyok.',
            'privacy_accepted' => '1',
            'password' => 'VerySecurePassword123!',
            'password_confirmation' => 'VerySecurePassword123!',
        ]);

        $response->assertRedirect(route('dashboard', [], false));
        $user = User::query()->where('email', 'jelentkezo@example.com')->firstOrFail();
        $this->assertSame('pending', $user->approval_status);
        $this->assertTrue(Hash::check('VerySecurePassword123!', $user->password));
        $this->assertNotSame('VerySecurePassword123!', $user->password);
        $this->assertDatabaseHas('member_profiles', [
            'user_id' => $user->id,
            'member_status' => 'pending',
            'cohort_year' => 2026,
        ]);
        $this->assertDatabaseHas('audit_entries', [
            'auditable_id' => $user->id,
            'event' => 'registration_submitted',
        ]);
        Notification::assertSentTo($president, FaktNotification::class);

        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_pending_account_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'approval_status' => 'pending',
            'password' => 'VerySecurePassword123!',
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'VerySecurePassword123!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_only_president_can_approve_a_registration(): void
    {
        Notification::fake();
        $president = $this->president();
        $member = $this->approvedMember('Aktív Tag');
        $applicant = User::factory()->create([
            'approval_status' => 'pending',
            'registration_note' => 'A FAKT közösségéhez tartozó jelentkező vagyok.',
        ]);
        $applicant->profile()->create(['member_status' => 'pending']);

        $this->actingAs($member)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->patch(route('admin.registrations.review', $applicant), ['status' => 'approved'])
            ->assertForbidden();

        $this->actingAs($president)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->patch(route('admin.registrations.review', $applicant), ['status' => 'approved'])
            ->assertRedirect();

        $applicant->refresh();
        $this->assertSame('approved', $applicant->approval_status);
        $this->assertSame($president->id, $applicant->approved_by);
        $this->assertNotNull($applicant->approved_at);
        $this->assertSame('active', $applicant->profile->member_status);
        Notification::assertSentTo($applicant, FaktNotification::class);
    }

    public function test_rejection_requires_a_reason_and_blocks_login(): void
    {
        Notification::fake();
        $president = $this->president();
        $applicant = User::factory()->create([
            'approval_status' => 'pending',
            'registration_note' => 'Jelentkezési indok legalább húsz karakterrel.',
            'password' => 'VerySecurePassword123!',
        ]);
        $applicant->profile()->create(['member_status' => 'pending']);

        $this->actingAs($president)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->patch(route('admin.registrations.review', $applicant), ['status' => 'rejected'])
            ->assertSessionHasErrors('decision_note');

        $this->actingAs($president)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->patch(route('admin.registrations.review', $applicant), [
                'status' => 'rejected',
                'decision_note' => 'A megadott adatok alapján a tagság nem azonosítható.',
            ])
            ->assertRedirect();

        $this->post(route('logout'))->assertRedirect('/');

        $this->post(route('login.store'), [
            'email' => $applicant->email,
            'password' => 'VerySecurePassword123!',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    private function president(): User
    {
        $semester = Semester::query()->firstOrCreate(
            ['name' => 'Aktív tesztfélév'],
            [
                'starts_at' => now()->subMonth(),
                'ends_at' => now()->addMonths(4),
                'is_active' => true,
            ]
        );
        $president = $this->approvedMember('Teszt Elnök');
        RoleAssignment::query()->create([
            'semester_id' => $semester->id,
            'user_id' => $president->id,
            'role' => 'president',
            'starts_at' => now()->subDay(),
            'ends_at' => $semester->ends_at,
        ]);

        return $president;
    }

    private function approvedMember(string $name): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'approval_status' => 'approved',
            'email_verified_at' => now(),
        ]);
        MemberProfile::query()->create([
            'user_id' => $user->id,
            'member_status' => 'active',
        ]);

        return $user;
    }
}
