<?php

namespace Modules\Employee\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Modules\Authentication\Models\User;
use Modules\Core\Traits\ApiResponse;
use Modules\Employee\Http\Requests\WorkerRequest;
use Modules\Employee\Http\Resources\WorkerResource;
use Modules\Employee\Models\Worker;

/**
 * A worker can view their own record; only users.manage can view/edit
 * anyone else's, or set status/work-authorization fields. This
 * deliberately does NOT touch employment relationship (CompanyWorker)
 * or contract (EmploymentContract) data — see those controllers instead.
 */
class WorkerController extends Controller
{
    use ApiResponse;

    public function show(User $user)
    {
        $worker = Worker::firstOrCreate(['user_id' => $user->id]);

        return $this->success(new WorkerResource($worker));
    }

    public function update(WorkerRequest $request, User $user)
    {
        $worker = Worker::firstOrCreate(['user_id' => $user->id]);
        $worker->update($request->validated());

        return $this->success(new WorkerResource($worker), 'Worker updated');
    }
}
