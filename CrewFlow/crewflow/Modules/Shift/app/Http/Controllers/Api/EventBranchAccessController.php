<?php

namespace Modules\Shift\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Shift\Models\Event;
use Modules\Shift\Models\EventBranchAccess;

/**
 * Authorization handled at the route level (permission:shifts.dispatch —
 * granting another branch visibility into an Event is a dispatching
 * concern). This grant alone does NOT make individual workers in that
 * branch see anything yet — see EventWorkerAccessController, which is
 * the actual per-worker activation step.
 */
class EventBranchAccessController extends Controller
{
    use ApiResponse;

    public function index(Event $event)
    {
        return $this->success($event->branchAccess()->with('branch')->get()->map(fn ($a) => [
            'id' => $a->id,
            'branch_id' => $a->branch_id,
            'branch_name' => $a->branch->name,
            'granted_by' => $a->granted_by,
            'created_at' => $a->created_at,
        ]));
    }

    public function store(Request $request, Event $event)
    {
        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id', 'different:'.$event->branch_id],
        ]);

        $access = EventBranchAccess::firstOrCreate(
            ['event_id' => $event->id, 'branch_id' => $data['branch_id']],
            ['granted_by' => $request->user()->id]
        );

        return $this->success($access, 'Branch access granted', 201);
    }

    public function destroy(Event $event, EventBranchAccess $branchAccess)
    {
        abort_unless($branchAccess->event_id === $event->id, 404);

        $branchAccess->delete();

        return $this->success(null, 'Branch access revoked');
    }
}
