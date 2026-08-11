<?php

namespace Modules\Shift\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Authentication\Models\User;

/**
 * A dispatcher's assignment of a specific worker to a Shift, and that
 * worker's own confirmation. This is the answer to "can a Company Admin
 * or Dispatcher assign work to a worker" — yes, via
 * ShiftController::assign() -> creates this record.
 *
 * MVP note: formal CancellationRequest (24h-notice rule, mandatory reason
 * when urgent) is deferred — for now, cancelling after assignment is a
 * direct status change by whoever has shifts.dispatch permission. The full
 * rule from the business-model doc will be implemented in a later pass.
 */
class Assignment extends Model
{
    protected $table = 'assignments';

    protected $fillable = [
        'shift_id',
        'worker_id',
        'assigned_by',
        'assigned_at',
        'transport_amount',
        'status',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'transport_amount' => 'decimal:2',
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

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
