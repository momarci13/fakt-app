<?php

namespace Tests\Feature;

use App\Models\AuditEntry;
use App\Models\Event;
use App\Models\MemberProfile;
use App\Models\OrgUnit;
use App\Models\RoleAssignment;
use App\Models\Semester;
use App\Models\Task;
use App\Models\User;
use App\Support\Audit;
use App\Support\SecureUpload;
use App\Support\UntrustedInput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_destructive_sql_input_is_rejected_without_touching_the_database(): void
    {
        $this->postJson(route('register.store'), $this->registration(['name' => 'DROP TABLE users']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('request');

        $this->assertTrue(Schema::hasTable('users'));
        $this->assertDatabaseCount('users', 0);
    }

    public function test_script_sql_union_path_traversal_and_invisible_text_are_rejected(): void
    {
        foreach (['<script>alert(1)</script>', 'x UNION SELECT password FROM users', '../.env', "Jó\u{202E}rossz"] as $payload) {
            $this->assertNotNull(UntrustedInput::stringViolation($payload, 'registration_note'));
        }
    }

    public function test_secret_values_are_not_scanned_as_sql_but_are_still_validated_by_password_rules(): void
    {
        $password = 'Drop table users! 2026 Strong';

        $this->post(route('register.store'), $this->registration([
            'email' => 'secret@example.test',
            'password' => $password,
            'password_confirmation' => $password,
        ]))->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'secret@example.test', 'approval_status' => 'pending']);
    }

    public function test_auth_pages_send_browser_hardening_and_no_cache_headers(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->assertStringContainsString("object-src 'none'", (string) $response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("frame-ancestors 'none'", (string) $response->headers->get('Content-Security-Policy'));
    }

    public function test_production_rejects_an_untrusted_host_header(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['security.trusted_host' => 'app.fakt.org.hu']);

        $this->withServerVariables(['HTTP_HOST' => 'attacker.example'])
            ->get('/login')
            ->assertBadRequest();
    }

    public function test_production_https_responses_include_hsts(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['security.trusted_host' => 'app.fakt.org.hu']);

        $this->withHeader('Host', 'app.fakt.org.hu')
            ->get('https://app.fakt.org.hu/login')
            ->assertOk()
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_pending_and_rejected_accounts_receive_the_same_login_failure(): void
    {
        $pending = User::factory()->create(['approval_status' => 'pending', 'password' => 'SamePassword!2026']);
        $rejected = User::factory()->create(['approval_status' => 'rejected', 'password' => 'SamePassword!2026']);

        $first = $this->post(route('login.store'), ['email' => $pending->email, 'password' => 'SamePassword!2026']);
        $second = $this->post(route('login.store'), ['email' => $rejected->email, 'password' => 'SamePassword!2026']);

        $first->assertSessionHasErrors('email');
        $second->assertSessionHasErrors('email');
        $this->assertSame(
            $first->getSession()->get('errors')->first('email'),
            $second->getSession()->get('errors')->first('email')
        );
        $this->assertGuest();
    }

    public function test_public_registration_is_rate_limited_by_identity_and_ip(): void
    {
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->post(route('register.store'), $this->registration([
                'email' => "limit{$attempt}@example.test",
            ]))->assertRedirect();
        }

        $this->post(route('register.store'), $this->registration(['email' => 'limit4@example.test']))
            ->assertTooManyRequests();
    }

    public function test_leaders_are_forced_to_enable_confirmed_mfa_when_policy_is_enabled(): void
    {
        config(['security.require_privileged_mfa' => true]);
        [$president] = $this->leaderAndSemester();

        $this->actingAs($president)->get(route('dashboard'))->assertRedirect(route('security.edit'));

        $president->forceFill([
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->actingAs($president->fresh())->get(route('dashboard'))->assertOk();
    }

    public function test_a_member_cannot_rsvp_to_an_event_outside_their_scope(): void
    {
        [$president, $semester] = $this->leaderAndSemester();
        $member = $this->approvedUser('Hatókörön kívüli tag');
        $team = OrgUnit::query()->create([
            'semester_id' => $semester->id,
            'type' => 'team',
            'name' => 'Zárt Team',
            'slug' => 'zart-team',
        ]);
        $event = Event::query()->create([
            'semester_id' => $semester->id,
            'org_unit_id' => $team->id,
            'organizer_id' => $president->id,
            'title' => 'Zárt esemény',
            'type' => 'team',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'visibility' => 'team',
            'obligation' => 'optional',
        ]);

        $this->actingAs($member)->put(route('calendar.rsvp', $event), ['response' => 'going'])
            ->assertNotFound();
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_historical_tasks_cannot_be_modified_through_a_record_id(): void
    {
        [, $activeSemester] = $this->leaderAndSemester();
        $member = $this->approvedUser('Történeti Tag');
        $historicalSemester = Semester::query()->create([
            'name' => 'Lezárt félév',
            'starts_at' => now()->subYear(),
            'ends_at' => now()->subMonths(7),
            'is_active' => false,
        ]);
        $task = Task::query()->create([
            'semester_id' => $historicalSemester->id,
            'created_by' => $member->id,
            'title' => 'Lezárt feladat',
            'status' => 'todo',
            'priority' => 'normal',
        ]);
        $task->assignees()->attach($member->id);

        $this->actingAs($member)->patch(route('tasks.update', $task), ['status' => 'done'])
            ->assertNotFound();
        $this->assertSame('todo', $task->fresh()->status);
        $this->assertTrue($activeSemester->is_active);
    }

    public function test_executable_content_disguised_as_pdf_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        SecureUpload::validate(
            UploadedFile::fake()->createWithContent('evidence.pdf', "<?php system('id');"),
            ['pdf']
        );
    }

    public function test_private_local_storage_has_no_automatic_public_route(): void
    {
        $this->assertFalse(collect(app('router')->getRoutes())->contains(
            fn ($route) => str_starts_with($route->uri(), 'storage/{path}')
        ));
    }

    public function test_active_pdf_payload_is_rejected_even_with_a_valid_signature(): void
    {
        $this->expectException(ValidationException::class);

        SecureUpload::validate(
            UploadedFile::fake()->createWithContent('evidence.pdf', "%PDF-1.7\n1 0 obj <</OpenAction 2 0 R>>"),
            ['pdf']
        );
    }

    public function test_audit_data_redacts_secrets_and_pseudonymizes_ip_addresses(): void
    {
        $user = $this->approvedUser('Audit Tag');

        $this->actingAs($user);
        Audit::record($user, 'security_test', null, [
            'name' => 'Audit Tag',
            'password' => 'must-not-appear',
            'nested' => ['evidence_path' => '/private/file.pdf'],
        ]);

        $audit = AuditEntry::query()->where('event', 'security_test')->firstOrFail();
        $this->assertSame('[REDACTED]', $audit->after['password']);
        $this->assertSame('[REDACTED]', $audit->after['nested']['evidence_path']);
        $this->assertStringStartsWith('ip#', (string) $audit->ip_address);
        $this->assertStringNotContainsString('127.0.0.1', (string) $audit->ip_address);
    }

    /** @return array<string, mixed> */
    private function registration(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Biztonsági Tesztelő',
            'email' => 'security@example.test',
            'cohort_year' => 2026,
            'registration_note' => 'Jogosult FAKT tagként kérem a hozzáférésemet.',
            'privacy_accepted' => '1',
            'password' => 'VerySecurePassword!2026',
            'password_confirmation' => 'VerySecurePassword!2026',
        ], $overrides);
    }

    /** @return array{User, Semester} */
    private function leaderAndSemester(): array
    {
        $semester = Semester::query()->create([
            'name' => 'Biztonsági félév',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addMonths(4),
            'is_active' => true,
        ]);
        $president = $this->approvedUser('Biztonsági Elnök');
        RoleAssignment::query()->create([
            'semester_id' => $semester->id,
            'user_id' => $president->id,
            'role' => 'president',
            'starts_at' => now()->subDay(),
            'ends_at' => $semester->ends_at,
        ]);

        return [$president, $semester];
    }

    private function approvedUser(string $name): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'approval_status' => 'approved',
            'email_verified_at' => now(),
        ]);
        MemberProfile::query()->create(['user_id' => $user->id, 'member_status' => 'active']);

        return $user;
    }
}
