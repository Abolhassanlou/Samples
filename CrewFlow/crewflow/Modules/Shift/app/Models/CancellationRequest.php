<?php

namespace Modules\Shift\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Authentication\Models\User;

/**
 * A worker's formal request to cancel their own Assignment. Doesn't
 * cancel it immediately — a dispatcher must review and approve/reject
 * (see project-business-model.md, section 5, rule 9). `is_urgent` is
 * computed at creation time: true if less than 24 hours remain before
 * the shift starts, in which case a `reason` is mandatory.
 */
class CancellationRequest extends Model
{
    protected $table = 'cancellation_requests';

    protected $fillable = [
        'assignment_id',
        'worker_id',
        'reason',
        'is_urgent',
        'status',
        'processed_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_urgent' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
