<?php

namespace Modules\Shift\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Employee\Models\Qualification;

/**
 * "This Shift requires qualification X" — a worker missing ANY of a
 * Shift's required qualifications never sees it at all (per the
 * project's visibility rule: hide, don't just disable). References
 * Employee's Qualification catalog directly (Shift already depends on
 * Employee).
 */
class ShiftQualification extends Model
{
    protected $table = 'shift_qualifications';

    protected $fillable = [
        'shift_id',
        'qualification_id',
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function qualification(): BelongsTo
    {
        return $this->belongsTo(Qualification::class);
    }
}
