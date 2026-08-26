<?php

namespace Modules\Shift\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Authentication\Models\User;

/**
 * A worker's expression of interest in a Shift (optionally in one
 * specific ShiftPosition/role, if the shift has any). Free to withdraw
 * any time before the dispatcher assigns someone. If the shift/position
 * is already full when interest is expressed, status becomes
 * "waitlisted" instead of "pending" — see ShiftInterestController.
 */
class ShiftInterest extends Model
{
    protected $table = 'shift_interests';

    protected $fillable = [
        'shift_id',
        'shift_position_id',
        'worker_id',
        'expressed_at',
        'withdrawn_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'expressed_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(ShiftPosition::class, 'shift_position_id');
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }
}
