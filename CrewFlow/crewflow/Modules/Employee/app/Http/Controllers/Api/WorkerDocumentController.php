<?php

namespace Modules\Employee\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Modules\Authentication\Models\User;
use Modules\Core\Traits\ApiResponse;
use Modules\Employee\Http\Resources\WorkerDocumentResource;
use Modules\Employee\Models\CustomDocumentType;
use Modules\Employee\Models\WorkerDocument;
use Modules\Employee\Models\WorkerQualification;

/**
 * FIXED_DOCUMENT_TYPES is the baseline every company gets for free —
 * CustomDocumentType (see that model/controller) only ever ADDS to this
 * list, never replaces it. Split into two categories, matching the
 * worker portal's Profile layout:
 *
 * - "personal" — general identity documents, shown under My info.
 *   Not tied to any specific job/event.
 * - "work" — job/event-related uploads (e.g. a timesheet or an
 *   event-specific certificate), shown under the top-level Documents
 *   section, alongside employment contracts (a separate concept
 *   entirely — see EmploymentContract).
 */

class WorkerDocumentController extends Controller
{
    use ApiResponse;

    private const FIXED_DOCUMENT_TYPES = [
        'photo' => 'personal',
        'passport' => 'personal',
        'identity_document' => 'personal', // ID card — an alternative to passport, not both required
        'insurance_card_front' => 'personal',
        'insurance_card_back' => 'personal',
        'bank_card' => 'personal',
        'resume' => 'personal',
        'work_permit_front' => 'personal',
        'work_permit_back' => 'personal',
        'driving_license' => 'personal', // optional — only if the worker actually has one
        'meldezettel' => 'personal', // Austrian residence registration certificate
        'residence_permit' => 'personal',
        'criminal_record' => 'personal',

        'certificate' => 'work',
        'other' => 'work',
    ];

    /**
     * The fixed baseline plus every active company-added type — the
     * full set of valid values for document_type right now.
     */
    private function allowedDocumentTypes(): array
    {
        return array_merge(
            array_keys(self::FIXED_DOCUMENT_TYPES),
            CustomDocumentType::where('is_active', true)->pluck('key')->all()
        );
    }

    /**
     * The fixed baseline, with human-readable labels and each one's
     * category — merge with GET /api/custom-document-types on the
     * frontend for the full dropdown (filtered to the right category).
     * Kept separate from that endpoint since the baseline isn't stored
     * in the database at all (it's this const array).
     */
    public function types(Request $request)
    {
        $labels = [
            'photo' => 'Photo',
            'passport' => 'Passport',
            'identity_document' => 'ID card',
            'insurance_card_front' => 'Insurance card (front)',
            'insurance_card_back' => 'Insurance card (back)',
            'bank_card' => 'Bank card',
            'resume' => 'Resume',
            'work_permit_front' => 'Work permit (front)',
            'work_permit_back' => 'Work permit (back)',
            'driving_license' => 'Driving license',
            'meldezettel' => 'Meldezettel (residence registration)',
            'residence_permit' => 'Residence permit',
            'criminal_record' => 'Criminal record check',
            'certificate' => 'Certificate',
            'other' => 'Other',
        ];

        $types = collect(self::FIXED_DOCUMENT_TYPES)
            // NOTE: $request->string() returns a Stringable OBJECT, not
            // a plain string — comparing it with === against a plain
            // string is ALWAYS false regardless of content (strict
            // comparison never coerces types), which silently filtered
            // this list down to nothing whenever ?category= was passed.
            // $request->query() returns the raw string value instead.
            ->when($request->filled('category'), fn ($c) => $c->filter(fn ($cat) => $cat === $request->query('category')))
            ->map(fn ($category, $key) => ['key' => $key, 'label' => $labels[$key], 'category' => $category])
            ->values();

        return $this->success($types);
    }

    /**
     * A worker's own documents. No special permission — a worker can
     * always see their own upload history and review status.
     */
    public function index(Request $request)
    {
        $documents = WorkerDocument::where('worker_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return $this->success(WorkerDocumentResource::collection($documents));
    }

    /**
     * The same thing as index(), but for a SPECIFIC other worker — what
     * an admin uses on that worker's detail page to see everything
     * they've uploaded (not just the pending ones — see pending()
     * below for the review queue). Gated by documents.review, the same
     * permission reviewing a document itself requires.
     */
    public function forWorker(User $user)
    {
        $documents = WorkerDocument::where('worker_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return $this->success(WorkerDocumentResource::collection($documents));
    }

    /**
     * Every document uploaded starts as "pending" — never auto-approved,
     * per the project's rule that qualifications always require a human
     * review, no matter how confident the upload looks.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'document_type' => ['required', Rule::in($this->allowedDocumentTypes())],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
            'document_number' => ['nullable', 'string', 'max:255'],
            'issued_at' => ['nullable', 'date'],
            'visa_type' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $path = $request->file('file')->store('worker-documents', 'local');

        $document = WorkerDocument::create([
            'worker_id' => $request->user()->id,
            'document_type' => $data['document_type'],
            'file_path' => $path,
            'document_number' => $data['document_number'] ?? null,
            'issued_at' => $data['issued_at'] ?? null,
            'visa_type' => $data['visa_type'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'review_status' => 'pending',
        ]);

        return $this->success(new WorkerDocumentResource($document), 'Document uploaded, pending review', 201);
    }

    /**
     * The review queue every admin/HR with documents.review works through.
     */
    public function pending()
    {
        $documents = WorkerDocument::where('review_status', 'pending')
            ->orderBy('created_at')
            ->get();

        return $this->success(WorkerDocumentResource::collection($documents));
    }

    /**
     * Approve or reject a document. Approving may optionally grant a
     * qualification in the same action (source=document_verified) —
     * still a human decision, just made in one request instead of two.
     */
    public function review(Request $request, WorkerDocument $document)
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'rejection_reason' => ['required_if:decision,rejected', 'nullable', 'string', 'max:500'],
            'qualification_id' => ['nullable', 'integer', 'exists:qualifications,id'],
        ]);

        $document->update([
            'review_status' => $data['decision'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => $data['decision'] === 'rejected' ? ($data['rejection_reason'] ?? null) : null,
        ]);

        if ($data['decision'] === 'approved' && ! empty($data['qualification_id'])) {
            WorkerQualification::firstOrCreate(
                ['worker_id' => $document->worker_id, 'qualification_id' => $data['qualification_id']],
                [
                    'source' => 'document_verified',
                    'supporting_document_id' => $document->id,
                    'granted_by' => $request->user()->id,
                    'granted_at' => now(),
                ]
            );
        }

        return $this->success(new WorkerDocumentResource($document), 'Document reviewed');
    }

    /**
     * Download the raw file. Only the worker themselves or someone with
     * documents.review may retrieve it.
     */
    public function download(Request $request, WorkerDocument $document)
    {
        $isOwner = $document->worker_id === $request->user()->id;
        $canReview = $request->user()->can('documents.review');

        abort_unless($isOwner || $canReview, 403);

        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path);
    }
}
