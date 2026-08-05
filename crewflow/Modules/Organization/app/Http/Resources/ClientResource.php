<?php

namespace Modules\Organization\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'default_contact_name' => $this->default_contact_name,
            'default_contact_phone' => $this->default_contact_phone,
            'default_address' => $this->default_address,
            'created_at' => $this->created_at,
        ];
    }
}
