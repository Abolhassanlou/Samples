<?php

namespace Modules\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'roles' => $this->getRoleNames(), // from HasRoles (spatie/laravel-permission)
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'created_at' => $this->created_at,
        ];
    }
}
