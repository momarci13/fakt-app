<?php

namespace App\Http\Controllers;

use App\Models\RoleAssignment;
use App\Models\Semester;
use App\Models\User;
use App\Notifications\FaktNotification;
use App\Support\CsvExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Rendszerállapot for the Elnök. Rackhost gives no SSH, so most checks used
 * to need a temporary cron and a log file; this page shows them directly.
 * No secret is ever shown: mail and database passwords are not read here.
 */
class SystemController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->isPresident(), 403);
        $lastTick = Cache::get('fakt:scheduler:last_tick');

        return Inertia::render('Admin/System', [
            'checks' => [
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
                'environment' => app()->environment(),
                'debug' => (bool) config('app.debug'),
                'timezone' => config('app.timezone'),
                'config_cached' => app()->configurationIsCached(),
                'routes_cached' => app()->routesAreCached(),
                'database' => $this->databaseOk(),
                'pending_migrations' => $this->pendingMigrations(),
                'storage_writable' => is_writable(storage_path('framework')) && is_writable(storage_path('logs')),
                'scheduler_last_tick' => $lastTick,
                'scheduler_ok' => $lastTick !== null && Carbon::parse($lastTick)->gt(now()->subMinutes(3)),
                'queue_jobs' => DB::table('jobs')->count(),
                'queue_oldest_minutes' => ($oldest = DB::table('jobs')->min('available_at')) ? (int) floor((time() - (int) $oldest) / 60) : null,
                'failed_jobs' => DB::table('failed_jobs')->count(),
                'mail' => [
                    'mailer' => config('mail.default'),
                    'host' => config('mail.mailers.smtp.host'),
                    'port' => config('mail.mailers.smtp.port'),
                    'from' => config('mail.from.address'),
                ],
            ],
            'failedJobs' => DB::table('failed_jobs')->latest('failed_at')->limit(5)->get(['id', 'queue', 'failed_at', 'exception'])
                ->map(fn ($job) => ['id' => $job->id, 'queue' => $job->queue, 'failed_at' => $job->failed_at, 'error' => mb_substr(strtok((string) $job->exception, "\n") ?: '', 0, 300)]),
            'logErrors' => $this->recentLogErrors(),
        ]);
    }

    /** Queues a test email to the Elnök; with the scheduler running it arrives within about a minute. */
    public function testMail(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isPresident(), 403);
        $request->user()->notify(new FaktNotification('Tesztlevél', 'Ha ezt emailben megkaptad, a levélküldés működik.', '/admin/rendszer', true));

        return back()->with('success', 'Tesztlevél sorba állítva. Egy percen belül meg kell érkeznie.');
    }

    public function exportMembers(Request $request): StreamedResponse
    {
        abort_unless($request->user()->isPresident(), 403);
        $semesterId = Semester::active()?->id;
        $roles = RoleAssignment::query()->where('semester_id', $semesterId)->whereNull('revoked_at')->get(['user_id', 'role'])->groupBy('user_id');
        $users = User::query()->with(['profile', 'teamMemberships' => fn ($q) => $q->where('semester_id', $semesterId)->with('orgUnit:id,name')])
            ->orderBy('name')->get(['id', 'name', 'email', 'approval_status', 'created_at']);

        return CsvExport::download('fakt-tagok-'.now()->format('Y-m-d').'.csv', ['Név', 'Email', 'Fiók', 'Tagi státusz', 'Évfolyam', 'Team', 'Tisztségek', 'Regisztráció'], $users->map(fn (User $user) => [
            $user->name, $user->email, $user->approval_status, $user->profile?->member_status, $user->profile?->cohort_year,
            $user->teamMemberships->first()?->orgUnit?->name,
            ($roles[$user->id] ?? collect())->pluck('role')->map(fn ($role) => MemberDirectoryController::ROLE_LABELS[$role] ?? $role)->unique()->implode(', '),
            $user->created_at?->format('Y-m-d'),
        ]));
    }

    private function databaseOk(): bool
    {
        try {
            DB::select('select 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /** @return list<string> */
    private function pendingMigrations(): array
    {
        try {
            $migrator = app('migrator');
            if (! $migrator->repositoryExists()) {
                return ['(a migrations tábla hiányzik)'];
            }
            $files = $migrator->getMigrationFiles(array_merge([database_path('migrations')], $migrator->paths()));

            return array_values(array_diff(array_keys($files), $migrator->getRepository()->getRan()));
        } catch (Throwable) {
            return ['(nem ellenőrizhető)'];
        }
    }

    /** @return list<string> The last few ERROR lines of the newest log file, shortened. */
    private function recentLogErrors(): array
    {
        $files = glob(storage_path('logs/laravel*.log')) ?: [];
        if ($files === []) {
            return [];
        }
        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));
        $handle = fopen($files[0], 'r');
        if ($handle === false) {
            return [];
        }
        $size = filesize($files[0]) ?: 0;
        fseek($handle, max(0, $size - 65536));
        $tail = (string) stream_get_contents($handle);
        fclose($handle);

        preg_match_all('/^\[[^\]]+\] \w+\.(?:ERROR|CRITICAL|ALERT|EMERGENCY):.*$/m', $tail, $matches);

        return array_map(fn ($line) => mb_substr($line, 0, 300), array_slice($matches[0], -8));
    }
}
