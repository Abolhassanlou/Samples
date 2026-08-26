<?php

namespace Modules\Shift\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Shift\Http\Requests\TransportGroupRequest;
use Modules\Shift\Http\Resources\TransportGroupResource;
use Modules\Shift\Models\Event;
use Modules\Shift\Models\TransportGroup;

/**
 * Authorization for every mutating action here is handled at the route
 * level (permission:shifts.dispatch — assigning transport is a
 * dispatching concern, same permission as assigning workers).
 */
class TransportGroupController extends Controller
{
    use ApiResponse;

    public function index(Event $event)
    {
        $groups = $event->transportGroups()->with(['driverAssignment.worker', 'passengerAssignments.worker'])->get();

        return $this->success(TransportGroupResource::collection($groups));
    }

    public function store(TransportGroupRequest $request, Event $event)
    {
        $data = $request->validated();

        $group = $event->transportGroups()->create([
            'driver_assignment_id' => $data['driver_assignment_id'],
            'vehicle_description' => $data['vehicle_description'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        if (! empty($data['passenger_assignment_ids'])) {
            $group->passengerAssignments()->attach($data['passenger_assignment_ids']);
        }

        return $this->success(new TransportGroupResource($group->load(['driverAssignment.worker', 'passengerAssignments.worker'])), 'Transport group created', 201);
    }

    public function update(TransportGroupRequest $request, Event $event, TransportGroup $transportGroup)
    {
        abort_unless($transportGroup->event_id === $event->id, 404);

        $data = $request->validated();

        $transportGroup->update([
            'driver_assignment_id' => $data['driver_assignment_id'],
            'vehicle_description' => $data['vehicle_description'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        if (isset($data['passenger_assignment_ids'])) {
            $transportGroup->passengerAssignments()->sync($data['passenger_assignment_ids']);
        }

        return $this->success(new TransportGroupResource($transportGroup->load(['driverAssignment.worker', 'passengerAssignments.worker'])), 'Transport group updated');
    }

    public function destroy(Event $event, TransportGroup $transportGroup)
    {
        abort_unless($transportGroup->event_id === $event->id, 404);

        $transportGroup->delete();

        return $this->success(null, 'Transport group deleted');
    }
}
