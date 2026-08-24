<?php

namespace Modules\Employee\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Traits\ApiResponse;
use Modules\Employee\Http\Resources\WorkerDocumentResource;
use Modules\Employee\Models\WorkerDocument;
use Modules\Employee\Models\WorkerQualification;

class WorkerDocumentController extends Controller
{
    use ApiResponse;

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
     * Every document uploaded starts as "pending" — never auto-approved,
     * per the project's rule that qualifications always require a human
     * review, no matter how confident the upload looks.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'document_type' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
            'visa_type' => ['nullable', 'string', 'max:255'],
            'visa_expiry_date' => ['nullable', 'date'],
        ]);

        $path = $request->file('file')->store('worker-documents', 'local');

        $document = WorkerDocument::create([
            'worker_id' => $request->user()->id,
            'document_type' => $data['document_type'],
            'file_path' => $path,
            'visa_type' => $data['visa_type'] ?? null,
            'visa_expiry_date' => $data['visa_expiry_date'] ?? null,
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
