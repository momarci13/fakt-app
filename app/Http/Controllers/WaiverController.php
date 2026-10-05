<?php

namespace App\Http\Controllers;

use App\Models\CourseOffering;
use App\Models\ObligationRule;
use App\Models\ObligationWaiver;
use App\Models\RoleAssignment;
use App\Models\Semester;
use App\Models\User;
use App\Notifications\FaktNotification;
use App\Support\Audit;
use App\Support\ElnoksegWaivers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Felmentések: waiving an obligation (a course's absence limit or a lifecycle
 * rule) needs the unanimous approval of the whole Elnökség.
 */
class WaiverController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $semester = Semester::active();
        $isElnokseg = $user->isElnoksegMember();

        $waivers = ObligationWaiver::query()
            ->where('semester_id', $semester?->id)
            ->when(! $isElnokseg, fn ($q) => $q->where('user_id', $user->id))
            ->with(['user:id,name', 'requester:id,name', 'course:id,title', 'votes.user:id,name'])
            ->orderByRaw("case when status = 'pending' then 0 else 1 end")
            ->latest()
            ->get();
        $voterNames = User::query()->whereIn('id', $this->elnoksegIds($semester))->pluck('name', 'id');

        return Inertia::render('Waivers/Index', [
            'waivers' => $waivers->map(fn (ObligationWaiver $waiver) => array_merge($waiver->toArray(), [
                'can_vote' => ElnoksegWaivers::canVote($user, $waiver),
                'my_vote' => $waiver->votes->firstWhere('user_id', $user->id)?->decision,
                'voters' => ElnoksegWaivers::voterIds($waiver)->map(fn (int $id) => [
                    'id' => $id,
                    'name' => $voterNames[$id] ?? '',
                    'decision' => $waiver->votes->firstWhere('user_id', $id)?->decision,
                ])->values(),
            ])),
            'isElnokseg' => $isElnokseg,
            'courses' => CourseOffering::query()->where('semester_id', $semester?->id)
                ->when(! $isElnokseg, fn ($q) => $q->whereHas('enrollments', fn ($e) => $e->where('user_id', $user->id)->where('status', 'approved')))
                ->orderBy('title')->get(['id', 'title']),
            'rules' => ObligationRule::query()->where('semester_id', $semester?->id)->where('is_active', true)->orderBy('name')->get(['code', 'name'])->unique('code')->values(),
            'members' => $isElnokseg ? User::query()->where('approval_status', 'approved')->orderBy('name')->get(['id', 'name']) : [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $semester = Semester::activeOrFail();
        $actor = $request->user();
        $data = $request->validate([
            'user_id' => ['nullable', Rule::exists('users', 'id')->where('approval_status', 'approved')],
            'course_offering_id' => ['nullable', 'required_without:obligation_rule_code', Rule::exists('course_offerings', 'id')->where('semester_id', $semester->id)],
            'obligation_rule_code' => ['nullable', 'required_without:course_offering_id', Rule::exists('obligation_rules', 'code')->where('semester_id', $semester->id)],
            'reason' => ['required', 'string', 'min:10', 'max:3000'],
        ]);
        $subjectId = (int) ($data['user_id'] ?? $actor->id);
        // A member asks for themselves; an Elnökség member may start one for anyone.
        abort_unless($subjectId === (int) $actor->id || $actor->isElnoksegMember(), 403);

        $duplicate = ObligationWaiver::query()->where('semester_id', $semester->id)->where('user_id', $subjectId)->where('status', 'pending')
            ->where('course_offering_id', $data['course_offering_id'] ?? null)->where('obligation_rule_code', $data['obligation_rule_code'] ?? null)->exists();
        if ($duplicate) {
            return back()->withErrors(['reason' => 'Erre már van folyamatban lévő felmentési kérelem.']);
        }

        $waiver = ObligationWaiver::query()->create([
            'semester_id' => $semester->id,
            'user_id' => $subjectId,
            'course_offering_id' => $data['course_offering_id'] ?? null,
            'obligation_rule_code' => isset($data['course_offering_id']) ? null : ($data['obligation_rule_code'] ?? null),
            'requested_by' => $actor->id,
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);
        Audit::record($waiver, 'waiver_requested');

        User::query()->whereIn('id', ElnoksegWaivers::voterIds($waiver))->whereKeyNot($actor->id)->get()->each->notify(
            new FaktNotification('Felmentési kérelem szavazásra vár', 'Az Elnökség egyhangú döntése szükséges.', '/felmentesek')
        );

        return back()->with('success', 'A felmentési kérelem az Elnökség elé került.');
    }

    public function vote(Request $request, ObligationWaiver $waiver): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required', Rule::in(['approve', 'reject'])], 'note' => ['nullable', 'string', 'max:1000']]);
        $waiver = ElnoksegWaivers::vote($waiver, $request->user(), $data['decision'], $data['note'] ?? null);

        return back()->with('success', match ($waiver->status) {
            'approved' => 'Szavazat rögzítve. Az Elnökség egyhangúlag megadta a felmentést.',
            'rejected' => 'Szavazat rögzítve. A kérelem elutasítva.',
            default => 'Szavazat rögzítve. További szavazatokra vár.',
        });
    }

    private function elnoksegIds(?Semester $semester)
    {
        return RoleAssignment::query()->where('semester_id', $semester?->id)->whereIn('role', ['president', 'vice_president'])->whereNull('revoked_at')->pluck('user_id');
    }
}
