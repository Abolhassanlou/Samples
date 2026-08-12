<?php

namespace Modules\Notification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Authentication\Models\User;

/**
 * Named "Alarm" (not "Notification") deliberately — Laravel already has
 * a built-in `notifications` table (used by its own Notification database
 * channel). Reusing that name here would risk exactly the kind of
 * collision documented in project-business-model.md, section 6.8.
 */
class Alarm extends Model
{
    protected $table = 'alarms';

    protected $fillable = [
        'worker_id',
        'shift_id',
        'type',
        'channel',
        'message',
        'sent_at',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }
}
