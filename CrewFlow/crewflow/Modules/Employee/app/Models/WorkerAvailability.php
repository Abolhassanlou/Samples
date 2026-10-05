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
        'date', // null = a weekly template row; set = available on exactly this date
        'day_of_week', // 0 (Sunday) - 6 (Saturday); for a dated row, that date's weekday
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }
}
