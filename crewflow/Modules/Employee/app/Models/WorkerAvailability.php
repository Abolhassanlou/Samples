<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Authentication\Models\User;

class WorkerAvailability extends Model
{
    protected $table = 'worker_availabilities';

    protected $fillable = [
        'worker_id',
        'day_of_week', // 0 (Sunday) - 6 (Saturday)
        'start_time',
        'end_time',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }
}
