<?php

namespace Modules\Shift\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Employee\Models\Qualification;

/**
 * "This Shift (or one specific role/position within it) requires
 * qualification X" — a worker missing a requirement they'd need for
 * EVERY role on the shift never sees it at all (per the project's
 * visibility rule: hide, don't just disable) — but if the shift has
 * several independent roles, qualifying for just one of them is enough
 * to see it. See ShiftVisibility for the full logic. References
 * Employee's Qualification catalog directly (Shift already depends on
 * Employee).
 */
class ShiftQualification extends Model
{
    protected $table = 'shift_qualifications';

    protected $fillable = [
        'shift_id',
        'shift_position_id',
        'qualification_id',
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(ShiftPosition::class, 'shift_position_id');
    }

    public function qualification(): BelongsTo
    {
        return $this->belongsTo(Qualification::class);
    }
}
