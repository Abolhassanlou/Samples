<?php

namespace Modules\Shift\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Authentication\Models\User;

/**
 * The actual per-worker "activation" — a specific worker (from a branch
 * that already has EventBranchAccess, or the Event's own home branch)
 * gets explicit visibility into this Event's shifts. This is what a
 * receiving branch's own admin/dispatcher creates, one worker at a time,
 * after their branch has been granted access.
 */
class EventWorkerAccess extends Model
{
    protected $table = 'event_worker_access';

    protected $fillable = [
        'event_id',
        'worker_id',
        'granted_by',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
