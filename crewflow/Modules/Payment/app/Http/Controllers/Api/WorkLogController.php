<?php

namespace Modules\Payment\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Authentication\Models\User;
use Modules\Core\Traits\ApiResponse;
use Modules\Payment\Http\Resources\WorkLogResource;
use Modules\Payment\Models\WorkLog;

class WorkLogController extends Controller
{
    use ApiResponse;

    /**
     * A worker's own pay history. Requires being that worker, or having
     * users.manage (an admin looking up anyone's history).
     */
    public function index(Request $request, User $user)
    {
        abort_unless(
            $request->user()->id === $user->id || $request->user()->can('users.manage'),
            403
        );

        $logs = WorkLog::where('worker_id', $user->id)->orderByDesc('work_date')->get();

        return $this->success(WorkLogResource::collection($logs));
    }
}
