<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\OrgUnit;
use App\Models\RoleAssignment;
use App\Models\Semester;
use App\Models\Task;
use App\Models\TeamMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Before the request-scoped memo, the Alelnök's task page ran 70 queries,
 * 22 of them the same active-semester lookup. These budgets keep it fixed
 * and independent of how much data a page shows.
 */
class QueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, User> */
    private array $users = [];

    protected function setUp(): void
    {
        parent::setUp();

        $semester = Semester::query()->create(['name' => 'S', 'starts_at' => now()->subMonth(), 'ends_at' => now()->addMonths(5), 'is_active' => true]);
        $portfolio = OrgUnit::query()->create(['semester_id' => $semester->id, 'type' => 'portfolio', 'name' => 'P', 'slug' => 'p']);
        $team = OrgUnit::query()->create(['semester_id' => $semester->id, 'type' => 'team', 'name' => 'T', 'slug' => 't', 'parent_id' => $portfolio->id]);
        $assign = fn (User $user, string $role, ?int $unit = null) => RoleAssignment::query()->create(['semester_id' => $semester->id, 'org_unit_id' => $unit, 'user_id' => $user->id, 'role' => $role, 'starts_at' => now()->subDay()->toDateString()]);

        foreach (['president' => null, 'vice_president' => $portfolio->id, 'team_leader' => $team->id] as $role => $unit) {
            $this->users[$role] = User::factory()->create(['approval_status' => 'approved']);
            $assign($this->users[$role], $role, $unit);
        }
        $members = User::factory()->count(20)->create(['approval_status' => 'approved']);
        $this->users['member'] = $members[0];
        foreach ($members as $member) {
            TeamMembership::query()->create(['semester_id' => $semester->id, 'org_unit_id' => $team->id, 'user_id' => $member->id, 'starts_at' => now()->subDay()->toDateString()]);
        }
        for ($i = 0; $i < 30; $i++) {
            $task = Task::query()->create(['semester_id' => $semester->id, 'org_unit_id' => $team->id, 'created_by' => $this->users['team_leader']->id, 'title' => "Feladat {$i}", 'due_at' => now()->addDays($i)]);
            $task->assignees()->sync([$members[$i % 20]->id]);
            Event::query()->create(['semester_id' => $semester->id, 'org_unit_id' => $team->id, 'organizer_id' => $this->users['team_leader']->id, 'title' => "Esemény {$i}", 'type' => 'team', 'starts_at' => now()->addDays($i), 'ends_at' => now()->addDays($i)->addHour(), 'visibility' => 'scope']);
        }
    }

    public static function pages(): array
    {
        return [
            'dashboard' => ['dashboard', 25],
            'tasks' => ['tasks.index', 22],
            'calendar' => ['calendar.index', 20],
            'courses' => ['courses.index', 14],
            'organization' => ['organization.index', 18],
        ];
    }

    #[DataProvider('pages')]
    public function test_pages_stay_within_their_query_budget(string $route, int $budget): void
    {
        foreach ($this->users as $label => $user) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($user)->get(route($route))->assertOk();
            $log = DB::getQueryLog();
            DB::disableQueryLog();

            $semesterReads = collect($log)->filter(fn ($q) => str_contains($q['query'], 'from "semesters" where "is_active"'))->count();
            $this->assertLessThanOrEqual(1, $semesterReads, "{$route} as {$label} read the active semester {$semesterReads} times");
            $this->assertLessThanOrEqual($budget, count($log), "{$route} as {$label} ran ".count($log)." queries (budget {$budget})");
        }
    }
}
