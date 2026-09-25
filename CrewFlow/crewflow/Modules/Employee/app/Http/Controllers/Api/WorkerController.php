<?php

namespace Modules\Employee\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Authentication\Models\User;
use Modules\Core\Traits\ApiResponse;
use Modules\Employee\Http\Requests\WorkerRequest;
use Modules\Employee\Http\Resources\WorkerResource;
use Modules\Employee\Models\Worker;

/**
 * A worker can view their own record; only users.manage can view/edit
 * anyone else's, or set status/work_authorization_status (a worker CAN
 * self-report their own work_authorization_type/expiry_date claim —
 * see update()'s docblock). This deliberately does NOT touch employment
 * relationship (CompanyWorker) or contract (EmploymentContract) data —
 * see those controllers instead.
 */
class WorkerController extends Controller
{
    use ApiResponse;

    public function show(Request $request, User $user)
    {
        abort_unless($request->user()->id === $user->id || $request->user()->can('users.manage'), 403);

        $worker = Worker::firstOrCreate(['user_id' => $user->id]);

        return $this->success(new WorkerResource($worker));
    }

    /**
     * A worker editing their own record can touch personal facts AND
     * now their own claimed work_authorization_type/expiry_date too —
     * e.g. "I have an Austrian passport" or "I have a Rot-Weiß-Rot Karte
     * expiring on X". This is just their OWN CLAIM, not a verified fact:
     * `status` and `work_authorization_status` stay stripped out unless
     * the requester has users.manage, so a worker can never self-approve
     * their own work authorization or activate themselves — an admin
     * still has to look at the uploaded passport/permit document (see
     * the Documents system) and set work_authorization_status
     * themselves before this worker becomes assignable
     * (WorkerEligibility gates on that status, never on the type/expiry
     * claim alone).
     *
     * `bank_account_holder_name` should match the worker's own name in
     * practice, but that's deliberately just a note shown on the
     * frontend, not a hard backend check — real legal names have enough
     * variation (diacritics, middle names, order, joint accounts) that
     * an automated match kept producing false rejections.
     */
    public function update(WorkerRequest $request, User $user)
    {
        $isSelf = $request->user()->id === $user->id;
        abort_unless($isSelf || $request->user()->can('users.manage'), 403);

        $data = $request->validated();
        if ($isSelf && ! $request->user()->can('users.manage')) {
            unset($data['status'], $data['work_authorization_status']);
        }

        $worker = Worker::firstOrCreate(['user_id' => $user->id]);
        $worker->update($data);

        return $this->success(new WorkerResource($worker), 'Worker updated');
    }
}
