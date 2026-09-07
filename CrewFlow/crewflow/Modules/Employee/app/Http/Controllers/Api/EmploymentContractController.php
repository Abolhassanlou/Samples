<?php

namespace Modules\Employee\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Authentication\Models\User;
use Modules\Core\Traits\ApiResponse;
use Modules\Employee\Http\Requests\EmploymentContractRequest;
use Modules\Employee\Http\Resources\EmploymentContractResource;
use Modules\Employee\Models\CompanyWorker;
use Modules\Employee\Models\EmploymentContract;
use Modules\Employee\Models\Worker;

/**
 * Full contract history for a worker — a worker can have many contracts
 * over time (e.g. today Geringfügig, a different contract a few months
 * later). Creating/editing a contract requires users.manage (an admin
 * decision). Viewing is self-or-admin — a worker needs to see their own
 * contracts to know what to sign. Signing (see sign() below) is
 * deliberately self-ONLY, never admin — it represents the worker's own
 * consent, not something anyone else can do on their behalf.
 */
class EmploymentContractController extends Controller
{
    use ApiResponse;

    public function index(Request $request, User $user)
    {
        abort_unless($request->user()->id === $user->id || $request->user()->can('users.manage'), 403);

        $companyWorker = $this->companyWorkerFor($user);

        $contracts = $companyWorker->contracts()->orderByDesc('start_date')->get();

        return $this->success(EmploymentContractResource::collection($contracts));
    }

    /**
     * Multipart — accepts the same fields as before plus an optional
     * `file` (the actual contract document a worker will read before
     * signing). A contract with no file can still exist (e.g. a rough
     * `draft`), but one moving to `pending_signature` should generally
     * have one attached — nothing stops the worker from signing without
     * it, but there's nothing for them to review either. Enforcing that
     * is left as a UI nudge, not a hard backend rule, since some very
     * informal `casual` arrangements may never need a document at all.
     */
    public function store(EmploymentContractRequest $request, User $user)
    {
        $companyWorker = $this->companyWorkerFor($user);

        $data = $request->validated();
        unset($data['file']);

        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('employment-contracts', 'local');
        }

        $contract = $companyWorker->contracts()->create($data);

        return $this->success(new EmploymentContractResource($contract), 'Contract created', 201);
    }

    public function update(EmploymentContractRequest $request, User $user, EmploymentContract $contract)
    {
        abort_unless($contract->company_worker_id === $this->companyWorkerFor($user)->id, 404);

        $data = $request->validated();
        unset($data['file']);

        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('employment-contracts', 'local');
        }

        $contract->update($data);

        return $this->success(new EmploymentContractResource($contract), 'Contract updated');
    }

    /**
     * The worker's own online signature/confirmation — only the worker
     * themselves can do this (not an admin on their behalf), and only
     * on a contract that's actually awaiting signature. Moves the
     * contract to "active", which is what makes it start counting
     * toward Shift's WorkerEligibility check.
     */
    public function sign(Request $request, User $user, EmploymentContract $contract)
    {
        abort_unless($request->user()->id === $user->id, 403);
        abort_unless($contract->company_worker_id === $this->companyWorkerFor($user)->id, 404);

        if ($contract->status !== 'pending_signature') {
            return $this->error('This contract is not currently awaiting signature.', 422);
        }

        $contract->update(['status' => 'active', 'signed_at' => now()]);

        return $this->success(new EmploymentContractResource($contract), 'Contract signed');
    }

    /**
     * Download the actual contract document — self or users.manage,
     * same access rule as viewing the contract list itself.
     */
    public function download(Request $request, User $user, EmploymentContract $contract)
    {
        abort_unless($request->user()->id === $user->id || $request->user()->can('users.manage'), 403);
        abort_unless($contract->company_worker_id === $this->companyWorkerFor($user)->id, 404);
        abort_unless($contract->file_path && Storage::disk('local')->exists($contract->file_path), 404);

        return Storage::disk('local')->download($contract->file_path);
    }

    private function companyWorkerFor(User $user): CompanyWorker
    {
        $worker = Worker::firstOrCreate(['user_id' => $user->id]);

        return CompanyWorker::firstOrCreate(['worker_id' => $worker->id]);
    }
}
