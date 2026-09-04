<?php

namespace Modules\Employee\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Modules\Authentication\Models\User;
use Modules\Core\Traits\ApiResponse;
use Modules\Employee\Http\Requests\CompanyWorkerRequest;
use Modules\Employee\Http\Resources\CompanyWorkerResource;
use Modules\Employee\Models\CompanyWorker;
use Modules\Employee\Models\Worker;

/**
 * The employment relationship itself — status (invited/pending/active/
 * inactive/blocked), the company's own employee number, and home
 * branch. Requires users.manage, same as Worker's own record.
 */
class CompanyWorkerController extends Controller
{
    use ApiResponse;

    public function show(User $user)
    {
        $worker = Worker::firstOrCreate(['user_id' => $user->id]);
        $companyWorker = CompanyWorker::with(['homeBranch', 'contracts'])->firstOrCreate(['worker_id' => $worker->id]);

        return $this->success(new CompanyWorkerResource($companyWorker));
    }

    public function update(CompanyWorkerRequest $request, User $user)
    {
        $worker = Worker::firstOrCreate(['user_id' => $user->id]);
        $companyWorker = CompanyWorker::firstOrCreate(['worker_id' => $worker->id]);
        $companyWorker->update($request->validated());

        return $this->success(new CompanyWorkerResource($companyWorker->load(['homeBranch', 'contracts'])), 'Employment status updated');
    }
}
