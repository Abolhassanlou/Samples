<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Authentication\Models\User;

class CustomFieldAnswer extends Model
{
    protected $table = 'custom_field_answers';

    protected $fillable = [
        'custom_field_definition_id',
        'worker_id',
        'value',
    ];

    public function fieldDefinition(): BelongsTo
    {
        return $this->belongsTo(CustomFieldDefinition::class, 'custom_field_definition_id');
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }
}
