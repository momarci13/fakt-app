<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Event;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(Request $request): Response
    {
        $isAlumni = (($nullsafeVariable1 = $request->user()->profile) ? $nullsafeVariable1->member_status : null) === 'alumni';
        $documents = Document::query()
            ->with('uploader:id,name')
            ->where(fn ($query) => $query
                ->whereIn('visibility', $isAlumni ? ['all', 'alumni'] : ['all', 'members'])
                ->orWhere('uploaded_by', $request->user()->id))
            ->latest()
            ->get();

        return Inertia::render('Documents/Index', [
            'documents' => $documents,
            'events' => $request->user()->isLeader() ? Event::query()->latest('starts_at')->take(50)->get(['id', 'title', 'starts_at']) : [],
            'canUpload' => $request->user()->isLeader(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isLeader(), 403);
        $data = $request->validate([
            'category' => ['required', Rule::in(['guidebook', 'minutes', 'evidence', 'other'])],
            'visibility' => ['required', Rule::in(['members', 'alumni', 'all'])],
            'event_id' => ['nullable', 'exists:events,id'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg'],
        ]);
        $file = $request->file('file');
        abort_unless($file !== null, 422);
        $allowedMimes = [
            'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'image/png', 'image/jpeg',
        ];
        abort_unless(in_array($file->getMimeType(), $allowedMimes, true), 422, 'A fájl valódi MIME-típusa nem engedélyezett.');

        $path = $file->store('documents/'.now()->format('Y/m'), 'local');
        $document = Document::query()->create([
            'uploaded_by' => $request->user()->id,
            'category' => $data['category'],
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'visibility' => $data['visibility'],
        ]);
        if (! empty($data['event_id'])) {
            $document->documentable()->associate(Event::query()->findOrFail($data['event_id']));
            $document->save();
        }
        Audit::record($document, 'uploaded');

        return back()->with('success', 'A dokumentum biztonságosan feltöltve.');
    }

    public function download(Request $request, Document $document): StreamedResponse
    {
        $isAlumni = (($nullsafeVariable2 = $request->user()->profile) ? $nullsafeVariable2->member_status : null) === 'alumni';
        $allowed = $request->user()->isPresident()
            || (int) $document->uploaded_by === (int) $request->user()->id
            || in_array($document->visibility, $isAlumni ? ['all', 'alumni'] : ['all', 'members'], true);
        abort_unless($allowed, 403);
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->original_name, ['Content-Type' => $document->mime_type]);
    }
}
