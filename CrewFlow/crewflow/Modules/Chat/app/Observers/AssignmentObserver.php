<?php

namespace Modules\Chat\Observers;

use Modules\Chat\Models\ChatConversation;
use Modules\Shift\Models\Assignment;

/**
 * Mirrors the Notification module's pattern exactly: Shift has no idea
 * this module exists. Whenever a worker is assigned to a Shift that
 * belongs to an Event, this makes sure that Event's team chat exists
 * (creating it on the very first assignment, dispatcher included) and
 * adds the newly assigned worker to it. Independent of any admin action
 * — the chat appears automatically as soon as assignments start.
 */
class AssignmentObserver
{
    public function created(Assignment $assignment): void
    {
        $shift = $assignment->shift;

        if (! $shift->event_id) {
            return;
        }

        $conversation = ChatConversation::where('event_id', $shift->event_id)
            ->where('type', 'group')
            ->first();

        if (! $conversation) {
            $event = $shift->event;

            $conversation = ChatConversation::create([
                'type' => 'group',
                'title' => ($event?->title ?? 'Event').' Team',
                'event_id' => $shift->event_id,
            ]);

            $conversation->participants()->attach($assignment->assigned_by);
        }

        $alreadyIn = $conversation->participants()->where('users.id', $assignment->worker_id)->exists();

        if (! $alreadyIn) {
            $conversation->participants()->attach($assignment->worker_id);
        }
    }
}
