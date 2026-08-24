<?php

namespace Modules\Employee\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Authentication\Models\User;
use Modules\Core\Traits\ApiResponse;
use Modules\Employee\Http\Resources\WorkerQualificationResource;
use Modules\Employee\Models\WorkerQualification;

/**
 * Authorization for every action here is handled at the route level
 * (permission:qualifications.manage).
 *
 * Only covers the "company_granted" path (e.g. after in-house training) —
 * the "document_verified" path happens via WorkerDocumentController::review(),
 * which creates one of these records when a document is approved and the
 * admin links it to a qualification.
 */
class WorkerQualificationController extends Controller
{
    use ApiResponse;

    public function index(User $user)
    {
        $qualifications = WorkerQualification::where('worker_id', $user->id)
            ->with('qualification')
            ->get();

        return $this->success(WorkerQualificationResource::collection($qualifications));
    }

    public function store(Request $request, User $user)
    {
        $data = $request->validate([
            'qualification_id' => ['required', 'integer', 'exists:qualifications,id'],
        ]);

        $existing = WorkerQualification::where('worker_id', $user->id)
            ->where('qualification_id', $data['qualification_id'])
            ->first();

        if ($existing) {
            return $this->error('This worker already has this qualification.', 422);
        }

        $workerQualification = WorkerQualification::create([
            'worker_id' => $user->id,
            'qualification_id' => $data['qualification_id'],
            'source' => 'company_granted',
            'granted_by' => $request->user()->id,
            'granted_at' => now(),
        ]);

        return $this->success(new WorkerQualificationResource($workerQualification->load('qualification')), 'Qualification granted', 201);
    }

    public function destroy(User $user, WorkerQualification $workerQualification)
    {
        abort_unless($workerQualification->worker_id === $user->id, 404);

        $workerQualification->delete();

        return $this->success(null, 'Qualification revoked');
    }
}
