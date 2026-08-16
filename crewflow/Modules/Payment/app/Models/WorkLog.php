<?php

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Authentication\Models\User;
use Modules\Shift\Models\Assignment;
use Modules\Shift\Models\Shift;

/**
 * Created once, when a worker's Assignment is marked complete (see
 * AssignmentCompletionController). hours_worked/base_amount are computed
 * from the Shift's own rate fields at that moment — copied here rather
 * than recalculated later, so a later rate change on the Shift never
 * silently changes what a worker was actually paid for past work.
 */
class WorkLog extends Model
{
    protected $table = 'work_logs';

    protected $fillable = [
        'assignment_id',
        'worker_id',
        'shift_id',
        'hours_worked',
        'base_amount',
        'transport_amount',
        'total_amount',
        'work_date',
    ];

    protected function casts(): array
    {
        return [
            'hours_worked' => 'decimal:2',
            'base_amount' => 'decimal:2',
            'transport_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'work_date' => 'date',
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

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }
}
