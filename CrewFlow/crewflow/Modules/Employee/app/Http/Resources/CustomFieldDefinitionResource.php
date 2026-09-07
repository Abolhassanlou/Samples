<?php

namespace Modules\Employee\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomFieldDefinitionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category,
            'key' => $this->key,
            'label' => $this->label,
            'field_type' => $this->field_type,
            'options' => $this->options,
            'is_required' => $this->is_required,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}
