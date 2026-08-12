<?php

namespace Modules\Notification\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Notification\Http\Resources\AlarmResource;
use Modules\Notification\Models\Alarm;

class AlarmController extends Controller
{
    use ApiResponse;

    /**
     * A worker's own notification center — no special permission, just
     * scoped to whoever is asking.
     */
    public function index(Request $request)
    {
        $alarms = Alarm::where('worker_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return $this->success(AlarmResource::collection($alarms));
    }

    public function markRead(Request $request, Alarm $alarm)
    {
        abort_unless($alarm->worker_id === $request->user()->id, 403);

        $alarm->update(['read_at' => now()]);

        return $this->success(new AlarmResource($alarm), 'Marked as read');
    }
}
