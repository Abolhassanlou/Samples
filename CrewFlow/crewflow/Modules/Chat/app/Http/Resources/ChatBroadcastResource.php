<?php

namespace Modules\Chat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Used by broadcastsIndex() — the history list. Deliberately no
 * per-recipient reply status here (that's what broadcastShow()'s
 * ChatBroadcastDetailResource is for) — computing has_replied for every
 * recipient of every past broadcast just to render a list would be far
 * more querying than a list screen needs.
 */
class ChatBroadcastResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'message' => $this->message,
            'sender_name' => $this->whenLoaded('sender', fn () => $this->sender->name),
            'recipient_count' => $this->whenCounted('recipients'),
            'sent_at' => $this->created_at,
        ];
    }
}
