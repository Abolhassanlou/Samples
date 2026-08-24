<?php

namespace Modules\Shift\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Authentication\Models\User;

/**
 * A worker's expression of interest in a Shift. Free to withdraw any time
 * before the dispatcher assigns someone (see project-business-model.md,
 * section 5, rule 9 for the full cancellation-after-assignment rule —
 * not yet implemented in this MVP pass, see Assignment's docblock).
 */
class ShiftInterest extends Model
{
    protected $table = 'shift_interests';

    protected $fillable = [
        'shift_id',
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

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }
}
