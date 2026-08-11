<?php

namespace Modules\Employee\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Authentication\Models\User;
use Modules\Core\Traits\ApiResponse;
use Modules\Employee\Http\Requests\WorkerAvailabilityRequest;
use Modules\Employee\Http\Resources\WorkerAvailabilityResource;
use Modules\Employee\Models\WorkerAvailability;

class WorkerAvailabilityController extends Controller
{
    use ApiResponse;

    public function index(User $user)
    {
        $slots = WorkerAvailability::where('worker_id', $user->id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return $this->success(WorkerAvailabilityResource::collection($slots));
    }

    /**
     * Full replace: a worker declares their whole weekly availability in
     * one request rather than adding/removing slots one at a time.
     */
    public function sync(WorkerAvailabilityRequest $request, User $user)
    {
        abort_unless(
            $request->user()->id === $user->id || $request->user()->can('users.manage'),
            403
        );

        WorkerAvailability::where('worker_id', $user->id)->delete();

        $slots = collect($request->validated('slots'))->map(fn ($slot) => WorkerAvailability::create([
            'worker_id' => $user->id,
            'day_of_week' => $slot['day_of_week'],
            'start_time' => $slot['start_time'],
            'end_time' => $slot['end_time'],
        ]));

        return $this->success(WorkerAvailabilityResource::collection($slots), 'Availability updated');
    }
}
