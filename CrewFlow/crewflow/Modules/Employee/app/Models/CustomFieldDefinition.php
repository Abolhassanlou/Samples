<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A company-defined question — e.g. "Shoe size" (select) or "Has manual
 * driving license?" (boolean). Appears on the worker's profile under
 * either the "Personal details" or "Skills" accordion, per `category`.
 * See this module's README for the full rationale (personal_info/skill
 * questions vary company to company, so they can't be hardcoded columns).
 */
class CustomFieldDefinition extends Model
{
    protected $table = 'custom_field_definitions';

    protected $fillable = [
        'category',
        'key',
        'label',
        'field_type',
        'options',
        'is_required',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function answers(): HasMany
    {
        return $this->hasMany(CustomFieldAnswer::class);
    }
}
