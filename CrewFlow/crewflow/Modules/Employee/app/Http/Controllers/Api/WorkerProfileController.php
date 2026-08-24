<?php

namespace Modules\Employee\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Modules\Authentication\Models\User;
use Modules\Core\Traits\ApiResponse;
use Modules\Employee\Http\Requests\WorkerProfileRequest;
use Modules\Employee\Http\Resources\WorkerProfileResource;
use Modules\Employee\Models\WorkerProfile;

/**
 * A worker can view their own profile; only users.manage can view/edit
 * anyone else's or set employment_type/hourly_rate (financial data).
 */
class WorkerProfileController extends Controller
{
    use ApiResponse;

    public function show(User $user)
    {
        $profile = WorkerProfile::firstOrCreate(['user_id' => $user->id]);

        return $this->success(new WorkerProfileResource($profile));
    }

    public function update(WorkerProfileRequest $request, User $user)
    {
        $profile = WorkerProfile::firstOrCreate(['user_id' => $user->id]);
        $profile->update($request->validated());

        return $this->success(new WorkerProfileResource($profile), 'Worker profile updated');
    }
}
