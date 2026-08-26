<?php

namespace Modules\Shift\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Authentication\Models\User;
use Modules\Organization\Models\Branch;

/**
 * Grants an ENTIRE other branch (not the Event's own home branch, which
 * always has default visibility) the ability to see this Event exists.
 * This alone does NOT make individual workers in that branch see the
 * shifts yet — that branch's own admin/dispatcher must still activate
 * specific workers via EventWorkerAccess. Two-step, deliberately: a
 * branch-level "you're allowed to look at this" followed by a
 * worker-level "you specifically are invited".
 */
class EventBranchAccess extends Model
{
    protected $table = 'event_branch_access';

    protected $fillable = [
        'event_id',
        'branch_id',
        'granted_by',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
