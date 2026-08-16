<?php

namespace Modules\Admin\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'display_name' => $this->display_name,
            'logo_url' => $this->logo_path ? url('/api/admin/company-profile/logo') : null,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'updated_at' => $this->updated_at,
        ];
    }
}
