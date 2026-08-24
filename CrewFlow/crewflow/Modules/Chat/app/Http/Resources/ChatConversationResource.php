<?php

namespace Modules\Chat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'participants' => $this->whenLoaded('participants', fn () => $this->participants->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
            ])),
            'last_message' => $this->whenLoaded('latestMessage', fn () => $this->latestMessage->first()?->message),
            'created_at' => $this->created_at,
        ];
    }
}
