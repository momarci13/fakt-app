<?php

namespace App\Http\Controllers;

use App\Models\Mentorship;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlumniController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Alumni/Index', [
            'alumni' => User::query()->where('approval_status', 'approved')->whereHas('profile', fn ($q) => $q->where('member_status', 'alumni')->where('alumni_visible', true))->with('profile')->orderBy('name')->get(['id', 'name', 'email']),
            'mentorships' => Mentorship::query()->where('mentor_id', $request->user()->id)->orWhere('mentee_id', $request->user()->id)->with(['mentor:id,name', 'mentee:id,name'])->get(),
        ]);
    }

    public function requestMentor(Request $request): RedirectResponse
    {
        $data = $request->validate(['mentor_id' => ['required', 'exists:users,id', 'different:'.'mentee_id'], 'focus' => ['required', 'string', 'max:500']]);
        $mentor = User::query()->whereKey($data['mentor_id'])->where('approval_status', 'approved')->whereHas('profile', fn ($q) => $q->where('member_status', 'alumni')->where('mentor_available', true)->where('alumni_visible', true))->firstOrFail();
        $mentorship = Mentorship::query()->updateOrCreate(['mentor_id' => $mentor->id, 'mentee_id' => $request->user()->id], ['status' => 'proposed', 'focus' => $data['focus']]);
        Audit::record($mentorship, 'mentorship_requested');

        return back()->with('success', 'A mentorálási kérést elküldtük.');
    }
}
