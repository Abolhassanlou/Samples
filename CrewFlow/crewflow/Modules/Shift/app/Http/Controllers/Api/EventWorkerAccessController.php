<?php

namespace Modules\Shift\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Employee\Models\CompanyWorker;
use Modules\Employee\Models\Worker;
use Modules\Shift\Models\Event;
use Modules\Shift\Models\EventWorkerAccess;

/**
 * Authorization handled at the route level (permission:shifts.dispatch).
 * A worker can only be activated here if their own home branch is either
 * the Event's own branch, or a branch that has already been granted
 * EventBranchAccess — enforced below, not just at the route level, since
 * this is specific business logic (not a blanket permission check).
 */
class EventWorkerAccessController extends Controller
{
    use ApiResponse;

    public function index(Event $event)
    {
        return $this->success($event->workerAccess()->with('worker')->get()->map(fn ($a) => [
            'id' => $a->id,
            'worker_id' => $a->worker_id,
            'worker_name' => $a->worker->name,
            'granted_by' => $a->granted_by,
            'created_at' => $a->created_at,
        ]));
    }

    public function store(Request $request, Event $event)
    {
        $data = $request->validate([
            'worker_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $workerRecord = Worker::where('user_id', $data['worker_id'])->first();
        $workerHomeBranchId = $workerRecord
            ? CompanyWorker::where('worker_id', $workerRecord->id)->value('home_branch_id')
            : null;

        $allowedBranchIds = $event->branchAccess()->pluck('branch_id')->push($event->branch_id);

        if (! $allowedBranchIds->contains($workerHomeBranchId)) {
            return $this->error('This worker\'s branch has not been granted access to this event yet.', 422);
        }

        $access = EventWorkerAccess::firstOrCreate(
            ['event_id' => $event->id, 'worker_id' => $data['worker_id']],
            ['granted_by' => $request->user()->id]
        );

        return $this->success($access, 'Worker activated for this event', 201);
    }

    public function destroy(Event $event, EventWorkerAccess $workerAccess)
    {
        abort_unless($workerAccess->event_id === $event->id, 404);

        $workerAccess->delete();

        return $this->success(null, 'Worker access revoked');
    }
}
