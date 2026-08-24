<?php

namespace Modules\Shift\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Shift\Http\Requests\EventRequest;
use Modules\Shift\Http\Resources\EventResource;
use Modules\Shift\Http\Resources\ShiftResource;
use Modules\Shift\Models\Event;

/**
 * Authorization for every mutating action here is handled entirely at
 * the route level (permission:shifts.create in routes/api.php).
 */
class EventController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->success(EventResource::collection(Event::withCount('shifts')->orderByDesc('starts_at')->get()));
    }

    public function store(EventRequest $request)
    {
        $event = Event::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return $this->success(new EventResource($event), 'Event created', 201);
    }

    public function show(Event $event)
    {
        return $this->success(new EventResource($event->loadCount('shifts')));
    }

    public function update(EventRequest $request, Event $event)
    {
        $event->update($request->validated());

        return $this->success(new EventResource($event), 'Event updated');
    }

    /**
     * Every Shift that belongs to this Event, for a quick overview
     * (e.g. "all the driver and guard shifts for this wedding").
     */
    public function shifts(Event $event)
    {
        return $this->success(ShiftResource::collection($event->shifts()->with('positions')->get()));
    }
}
