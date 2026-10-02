<?php

namespace Modules\Chat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_id' => $this->sender_id,
            'sender_name' => $this->whenLoaded('sender', fn () => $this->sender->name),
            'message' => $this->message,
            // True only for a recipient's copy of a broadcast — see
            // ChatController::messages()'s 30-day archive filter, which
            // this flag has nothing to do with enforcing (that's a
            // where() clause server-side); it's just so the frontend
            // can show an "Announcement" label if it wants to.
            'is_broadcast' => $this->broadcast_id !== null,
            'created_at' => $this->created_at,
        ];
    }
}
