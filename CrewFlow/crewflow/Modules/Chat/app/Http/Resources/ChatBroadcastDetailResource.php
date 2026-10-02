<?php

namespace Modules\Chat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Used by broadcastShow() — full recipient roster with reply status.
 * `has_replied` is set on each recipient model beforehand by the
 * controller (a live check, not a stored column — see
 * ChatBroadcastRecipient's own docblock for why).
 */
class ChatBroadcastDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'message' => $this->message,
            'sender_name' => $this->whenLoaded('sender', fn () => $this->sender->name),
            'sent_at' => $this->created_at,
            'recipients' => $this->recipients->map(fn ($r) => [
                'user_id' => $r->user_id,
                'name' => $r->user->name,
                'conversation_id' => $r->conversation_id,
                'has_replied' => $r->has_replied,
            ]),
        ];
    }
}
