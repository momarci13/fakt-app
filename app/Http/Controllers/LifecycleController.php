<?php

namespace App\Http\Controllers;

use App\Models\MemberRequest;
use App\Models\ProgressRecord;
use App\Models\Semester;
use App\Support\AccessScope;
use App\Support\Audit;
use App\Support\LifecycleProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LifecycleController extends Controller
{
    public function index(Request $request): Response
    {
        $semester = Semester::active();

        return Inertia::render('Lifecycle/Index', [
            'progress' => LifecycleProgress::for($request->user(), $semester),
            'records' => ProgressRecord::query()->where('user_id', $request->user()->id)->where('semester_id', $semester?->id)->latest()->get(),
            'requests' => MemberRequest::query()->where('user_id', $request->user()->id)->where('semester_id', $semester?->id)->latest()->get(),
            'memberStatus' => $request->user()->profile?->member_status,
        ]);
    }

    public function storeRequest(Request $request): RedirectResponse
    {
        $semester = Semester::activeOrFail();
        $data = $request->validate([
            'type' => ['required', Rule::in(['passivation', 'senior', 'diploma', 'exception'])],
            'reason' => ['required', 'string', 'max:5000'],
            'evidence' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg'],
        ]);
        $path = $request->file('evidence')?->store('evidence/'.$request->user()->id, 'local');
        $memberRequest = MemberRequest::query()->create(['user_id' => $request->user()->id, 'semester_id' => $semester->id, 'type' => $data['type'], 'reason' => $data['reason'], 'evidence_path' => $path, 'status' => 'pending']);
        Audit::record($memberRequest, 'submitted');

        return back()->with('success', 'A kérelmet benyújtottad.');
    }

    public function addProgress(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isPresident() || AccessScope::managesCourses($request->user()), 403);
        $semester = Semester::activeOrFail();
        $data = $request->validate(['user_id' => ['required', 'exists:users,id'], 'type' => ['required', 'string', 'max:80'], 'value' => ['required', 'numeric', 'between:-10,100'], 'note' => ['nullable', 'string', 'max:2000']]);
        $record = ProgressRecord::query()->create([...$data, 'semester_id' => $semester->id, 'status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now()]);
        Audit::record($record, 'progress_approved');

        return back()->with('success', 'Az előrehaladási rekord rögzítve.');
    }

    public function downloadEvidence(Request $request, MemberRequest $memberRequest)
    {
        abort_unless($memberRequest->user_id === $request->user()->id || $request->user()->isPresident(), 403);
        abort_unless($memberRequest->evidence_path && Storage::disk('local')->exists($memberRequest->evidence_path), 404);

        return Storage::disk('local')->download($memberRequest->evidence_path);
    }
}
