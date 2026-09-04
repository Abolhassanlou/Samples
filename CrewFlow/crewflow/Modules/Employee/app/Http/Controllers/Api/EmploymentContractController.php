<?php

namespace Modules\Employee\Http\Controllers\Api;

use Illuminate\Routing\Controller;
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
 * later). Requires users.manage throughout, same as the rest of a
 * worker's employment data.
 */
class EmploymentContractController extends Controller
{
    use ApiResponse;

    public function index(User $user)
    {
        $companyWorker = $this->companyWorkerFor($user);

        $contracts = $companyWorker->contracts()->orderByDesc('start_date')->get();

        return $this->success(EmploymentContractResource::collection($contracts));
    }

    public function store(EmploymentContractRequest $request, User $user)
    {
        $companyWorker = $this->companyWorkerFor($user);

        $contract = $companyWorker->contracts()->create($request->validated());

        return $this->success(new EmploymentContractResource($contract), 'Contract created', 201);
    }

    public function update(EmploymentContractRequest $request, User $user, EmploymentContract $contract)
    {
        abort_unless($contract->company_worker_id === $this->companyWorkerFor($user)->id, 404);

        $contract->update($request->validated());

        return $this->success(new EmploymentContractResource($contract), 'Contract updated');
    }

    private function companyWorkerFor(User $user): CompanyWorker
    {
        $worker = Worker::firstOrCreate(['user_id' => $user->id]);

        return CompanyWorker::firstOrCreate(['worker_id' => $worker->id]);
    }
}
