<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AuditEntry;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\MemberProfile;
use App\Models\MemberRequest;
use App\Models\ObligationRule;
use App\Models\Semester;
use App\Models\User;
use App\Notifications\FaktNotification;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizePresident($request);
        $semester = Semester::active();

        return Inertia::render('Admin/Index', [
            'semester' => $semester,
            'semesters' => Semester::query()->latest('starts_at')->get(),
            'rules' => ObligationRule::query()->where('semester_id', ($nullsafeVariable1 = $semester) ? $nullsafeVariable1->id : null)->orderBy('code')->orderByDesc('version')->get(),
            'audits' => AuditEntry::query()->with('actor:id,name')->latest('created_at')->take(25)->get(),
            'pendingRequests' => MemberRequest::query()->where('status', 'pending')->with('user:id,name,email')->latest()->get(),
            'pendingRegistrations' => User::query()
                ->where('approval_status', 'pending')
                ->with('profile:user_id,cohort_year,member_status')
                ->oldest()
                ->get(['id', 'name', 'email', 'registration_note', 'created_at']),
            'importBatches' => ImportBatch::query()->with(['rows' => fn ($query) => $query->orderBy('row_number')->limit(8)])->latest()->take(10)->get(),
            'stats' => [
                'users' => User::query()->where('approval_status', 'approved')->count(),
                'active' => MemberProfile::query()->whereIn('member_status', ['active', 'senior'])->count(),
                'alumni' => MemberProfile::query()->where('member_status', 'alumni')->count(),
                'pending' => User::query()->where('approval_status', 'pending')->count(),
            ],
        ]);
    }

    public function invite(Request $request): RedirectResponse
    {
        $this->authorizePresident($request);
        $data = $request->validate(['name' => ['required', 'string', 'max:150'], 'email' => ['required', 'email', 'max:255', 'unique:users,email'], 'member_status' => ['required', Rule::in(['active', 'senior', 'alumni'])], 'cohort_year' => ['nullable', 'integer', 'between:2008,2100']]);
        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make(Str::random(64)),
            'invited_by' => $request->user()->id,
            'invited_at' => now(),
            'calendar_token' => Str::random(48),
            'approval_status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);
        $user->profile()->create(['member_status' => $data['member_status'], 'cohort_year' => $data['cohort_year'] ?? null, 'alumni_visible' => false]);
        Password::sendResetLink(['email' => $user->email]);
        Audit::record($user, 'invited');

        return back()->with('success', 'A meghívó és jelszóbeállító email elküldve.');
    }

    public function stageMemberImport(Request $request): RedirectResponse
    {
        $this->authorizePresident($request);
        $request->validate(['file' => ['required', 'file', 'max:10240', 'mimes:csv,txt,xlsx']]);
        $file = $request->file('file');
        abort_unless($file !== null, 422);

        $rows = strtolower($file->getClientOriginalExtension()) === 'xlsx'
            ? $this->readXlsx($file->getRealPath())
            : $this->readCsv($file->getRealPath());

        abort_if($rows === [], 422, 'Az importfájl üres vagy nem olvasható.');
        $headers = array_map(fn ($value) => $this->normaliseHeader((string) $value), array_shift($rows));
        $required = ['name', 'email', 'member_status'];
        abort_unless(array_diff($required, $headers) === [], 422, 'Kötelező oszlopok: name, email, member_status.');

        $seenEmails = [];
        $batch = DB::transaction(function () use ($request, $file, $rows, $headers, &$seenEmails): ImportBatch {
            $batch = ImportBatch::query()->create([
                'uploaded_by' => $request->user()->id,
                'original_name' => $file->getClientOriginalName(),
                'mapping' => array_combine($headers, $headers),
            ]);

            $valid = 0;
            $invalid = 0;
            foreach ($rows as $index => $values) {
                if (count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) {
                    continue;
                }

                $values = array_pad($values, count($headers), null);
                $payload = array_combine($headers, array_slice($values, 0, count($headers)));
                $payload = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $payload);
                $payload['member_status'] = strtolower((string) ($payload['member_status'] ?? ''));
                $email = strtolower((string) ($payload['email'] ?? ''));
                $errors = [];

                if (trim((string) ($payload['name'] ?? '')) === '') {
                    $errors[] = 'A név kötelező.';
                }
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Érvénytelen email cím.';
                }
                if (! in_array($payload['member_status'], ['active', 'passive', 'senior', 'alumni'], true)) {
                    $errors[] = 'A státusz csak active, passive, senior vagy alumni lehet.';
                }
                if (($payload['cohort_year'] ?? '') !== '' && (! is_numeric($payload['cohort_year']) || (int) $payload['cohort_year'] < 2008 || (int) $payload['cohort_year'] > 2100)) {
                    $errors[] = 'Az évfolyam 2008 és 2100 közötti szám legyen.';
                }
                if (isset($seenEmails[$email]) || User::query()->where('email', $email)->exists()) {
                    $errors[] = 'Duplikált vagy már létező email cím.';
                }
                $seenEmails[$email] = true;

                ImportRow::query()->create([
                    'import_batch_id' => $batch->id,
                    'row_number' => $index + 2,
                    'payload' => $payload,
                    'errors' => $errors ?: null,
                    'status' => $errors ? 'invalid' : 'valid',
                ]);
                $errors ? $invalid++ : $valid++;
            }

            $batch->update([
                'total_rows' => $valid + $invalid,
                'valid_rows' => $valid,
                'invalid_rows' => $invalid,
                'reconciliation' => ['new_users' => $valid, 'duplicates_or_invalid' => $invalid],
            ]);

            return $batch;
        });
        Audit::record($batch, 'import_staged');

        return back()->with('success', "Import előnézet elkészült: {$batch->valid_rows} érvényes, {$batch->invalid_rows} hibás sor.");
    }

    public function applyMemberImport(Request $request, ImportBatch $importBatch): RedirectResponse
    {
        $this->authorizePresident($request);
        abort_unless($importBatch->status === 'staged' && (int) $importBatch->invalid_rows === 0, 422, 'Csak hibamentes, még nem alkalmazott import indítható.');

        DB::transaction(function () use ($request, $importBatch): void {
            foreach ($importBatch->rows()->where('status', 'valid')->get() as $row) {
                $payload = $row->payload;
                $user = User::query()->create([
                    'name' => $payload['name'],
                    'email' => strtolower($payload['email']),
                    'password' => Hash::make(Str::random(64)),
                    'invited_by' => $request->user()->id,
                    'invited_at' => now(),
                    'calendar_token' => Str::random(48),
                    'approval_status' => 'approved',
                    'approved_by' => $request->user()->id,
                    'approved_at' => now(),
                ]);
                $user->profile()->create([
                    'member_status' => $payload['member_status'],
                    'cohort_year' => ($payload['cohort_year'] ?? null) ?: null,
                    'alumni_visible' => false,
                ]);
                $row->createdRecord()->associate($user);
                $row->update(['status' => 'applied']);
            }
            $importBatch->update(['status' => 'applied', 'applied_at' => now()]);
        });
        Audit::record($importBatch, 'import_applied');

        return back()->with('success', 'Az import alkalmazva. A létrehozott fiókoknak külön küldhető meghívó.');
    }

    public function rollbackMemberImport(Request $request, ImportBatch $importBatch): RedirectResponse
    {
        $this->authorizePresident($request);
        abort_unless($importBatch->status === 'applied', 422, 'Csak alkalmazott import vonható vissza.');

        DB::transaction(function () use ($importBatch): void {
            $userIds = $importBatch->rows()->where('created_record_type', User::class)->pluck('created_record_id')->filter();
            User::query()->whereIn('id', $userIds)->whereNull('last_seen_at')->delete();
            $importBatch->rows()->update(['status' => 'rolled_back']);
            $importBatch->update(['status' => 'rolled_back', 'rolled_back_at' => now()]);
        });
        Audit::record($importBatch, 'import_rolled_back');

        return back()->with('success', 'Az import visszavonva; a már használt fiókok biztonsági okból megmaradtak.');
    }

    /** @return array<int, array<int, string|null>> */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        abort_if($handle === false, 422, 'A fájl nem olvasható.');
        $rows = [];
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if (count($row) === 1 && strpos((string) $row[0], ';') !== false) {
                $row = str_getcsv((string) $row[0], ';');
            }
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    /** @return array<int, array<int, string|null>> */
    private function readXlsx(string $path): array
    {
        abort_unless(class_exists(\ZipArchive::class), 422, 'Az XLSX importhoz a PHP zip bővítmény szükséges.');
        $zip = new \ZipArchive;
        abort_unless($zip->open($path) === true, 422, 'Az XLSX fájl nem nyitható meg.');
        $shared = [];
        if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $document = simplexml_load_string($xml);
            abort_if($document === false, 422, 'Az XLSX szövegtáblája sérült.');
            foreach ($document->si as $item) {
                $shared[] = (string) ($item->t ?? $item->r->t ?? '');
            }
        }
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        abort_if($sheet === false, 422, 'Az XLSX első munkalapja nem olvasható.');
        $document = simplexml_load_string($sheet);
        abort_if($document === false, 422, 'Az XLSX első munkalapja sérült.');
        $rows = [];
        foreach ($document->sheetData->row as $row) {
            $values = [];
            foreach ($row->c as $cell) {
                $reference = (string) $cell['r'];
                preg_match('/^[A-Z]+/', $reference, $match);
                $column = 0;
                foreach (str_split($match[0] ?? 'A') as $letter) {
                    $column = $column * 26 + ord($letter) - 64;
                }
                $raw = (string) $cell->v;
                $value = (string) $cell['t'] === 's' ? ($shared[(int) $raw] ?? '') : $raw;
                $values[$column - 1] = $value;
            }
            if ($values !== []) {
                ksort($values);
                $rows[] = array_replace(array_fill(0, max(array_keys($values)) + 1, null), $values);
            }
        }

        return $rows;
    }

    private function normaliseHeader(string $header): string
    {
        switch (strtolower(trim($header))) {
            case 'név':
            case 'nev':
            case 'teljes_név':
            case 'teljes_nev':
                return 'name';
            case 'e-mail':
            case 'email_cím':
            case 'email_cim':
                return 'email';
            case 'státusz':
            case 'statusz':
                return 'member_status';
            case 'évfolyam':
            case 'evfolyam':
                return 'cohort_year';
            default:
                return strtolower(trim($header));
        }
    }

    public function storeSemester(Request $request): RedirectResponse
    {
        $this->authorizePresident($request);
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'starts_at' => ['required', 'date'], 'ends_at' => ['required', 'date', 'after:starts_at'], 'activate' => ['boolean']]);
        if ($data['activate'] ?? false) {
            Semester::query()->update(['is_active' => false]);
        }
        $semester = Semester::query()->create(array_merge(collect($data)->except('activate')->all(), ['is_active' => $data['activate'] ?? false]));
        Audit::record($semester, 'created');

        return back()->with('success', 'A félév létrejött.');
    }

    public function storeRule(Request $request): RedirectResponse
    {
        $this->authorizePresident($request);
        $semester = Semester::activeOrFail();
        $data = $request->validate(['code' => ['required', 'alpha_dash', 'max:80'], 'name' => ['required', 'string', 'max:150'], 'description' => ['nullable', 'string', 'max:2000'], 'kind' => ['required', Rule::in(['minimum', 'maximum'])], 'threshold' => ['required', 'numeric', 'between:0,100']]);
        $version = (int) ObligationRule::query()->where('semester_id', $semester->id)->where('code', $data['code'])->max('version') + 1;
        $effectiveAt = now()->isAfter($semester->starts_at) ? now() : $semester->starts_at->startOfDay();
        $rule = ObligationRule::query()->create(array_merge($data, ['semester_id' => $semester->id, 'version' => $version, 'effective_at' => $effectiveAt, 'is_active' => true]));
        Audit::record($rule, 'rule_version_created');

        return back()->with('success', 'Az új szabályverzió piszkozatként létrejött.');
    }

    public function publishRules(Request $request): RedirectResponse
    {
        $this->authorizePresident($request);
        $semester = Semester::activeOrFail();
        $semester->update(['rules_published_at' => now()]);
        ObligationRule::query()->where('semester_id', $semester->id)->whereNull('published_at')->update(['published_at' => now(), 'published_by' => $request->user()->id]);
        Audit::record($semester, 'rules_published');

        return back()->with('success', 'A szabályok publikálva és lezárva.');
    }

    public function announce(Request $request): RedirectResponse
    {
        $this->authorizePresident($request);
        $data = $request->validate(['title' => ['required', 'string', 'max:160'], 'body' => ['required', 'string', 'max:5000'], 'audience' => ['required', Rule::in(['members', 'alumni', 'all'])], 'is_pinned' => ['boolean']]);
        $announcement = Announcement::query()->create(array_merge($data, ['semester_id' => ($nullsafeVariable2 = Semester::active()) ? $nullsafeVariable2->id : null, 'author_id' => $request->user()->id, 'published_at' => now()]));
        Audit::record($announcement, 'published');

        return back()->with('success', 'A közlemény megjelent.');
    }

    public function reviewMemberRequest(Request $request, MemberRequest $memberRequest): RedirectResponse
    {
        $this->authorizePresident($request);
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected'])], 'decision_note' => ['required', 'string', 'max:2000']]);
        $before = $memberRequest->toArray();
        $memberRequest->update(array_merge($data, ['reviewed_by' => $request->user()->id, 'reviewed_at' => now()]));
        $memberRequest->user->notify(new FaktNotification('Tagi kérelmedet elbírálták', $data['decision_note'], '/eletut'));

        if ($data['status'] === 'approved') {
            $profile = User::query()->findOrFail($memberRequest->user_id)->profile;
            switch ($memberRequest->type) {
                case 'passivation':
                    ($nullsafeVariable3 = $profile) ? $nullsafeVariable3->update(['member_status' => 'passive']) : null;
                    break;
                case 'senior':
                    ($nullsafeVariable4 = $profile) ? $nullsafeVariable4->update(['member_status' => 'senior']) : null;
                    break;
                case 'diploma':
                    ($nullsafeVariable5 = $profile) ? $nullsafeVariable5->update(['member_status' => 'alumni', 'diploma_awarded_at' => now()->toDateString(), 'alumni_visible' => false]) : null;
                    break;
                default:
                    null;
                    break;
            }
        }
        Audit::record($memberRequest, 'reviewed', $before);

        return back()->with('success', 'A tagi kérelem elbírálva.');
    }

    public function reviewRegistration(Request $request, User $user): RedirectResponse
    {
        $this->authorizePresident($request);
        abort_unless($user->approval_status === 'pending', 422, 'Ez a regisztráció már el lett bírálva.');

        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'decision_note' => [Rule::requiredIf($request->input('status') === 'rejected'), 'nullable', 'string', 'max:2000'],
        ]);
        $before = $user->toArray();

        DB::transaction(function () use ($request, $user, $data): void {
            if ($data['status'] === 'approved') {
                $user->update([
                    'approval_status' => 'approved',
                    'approved_by' => $request->user()->id,
                    'approved_at' => now(),
                    'rejected_at' => null,
                    'rejection_reason' => null,
                ]);
                $user->profile()->update(['member_status' => 'active']);
            } else {
                $user->update([
                    'approval_status' => 'rejected',
                    'rejected_at' => now(),
                    'rejection_reason' => $data['decision_note'],
                ]);
                $user->profile()->update(['member_status' => 'rejected']);
            }
        });

        $approved = $data['status'] === 'approved';
        $user->notify(new FaktNotification(
            $approved ? 'Regisztrációd jóváhagyva' : 'Regisztrációs döntés',
            $approved
                ? 'Az Elnök jóváhagyta a hozzáférésedet. Az email címed ellenőrzése után beléphetsz.'
                : $data['decision_note'],
            '/login'
        ));
        Audit::record($user, $approved ? 'registration_approved' : 'registration_rejected', $before);

        return back()->with('success', $approved
            ? 'A regisztráció jóváhagyva; a jelentkező értesítést kapott.'
            : 'A regisztráció elutasítva; a döntés naplózva lett.');
    }

    private function authorizePresident(Request $request): void
    {
        abort_unless($request->user()->isPresident(), 403);
    }
}
