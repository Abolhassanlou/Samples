<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A READ-ONLY view into crewflow's own `tenants` table, via the 'central'
 * database connection (see config/database.php — should use read-only
 * MySQL credentials at the DB-user level, not just by convention here).
 * Never write through this model — suspend/unsuspend, subscription
 * changes, etc. must go through CrewflowApiClient instead, so crewflow's
 * own validation/events are never bypassed. See this project's README.
 */
class Company extends Model
{
    protected $connection = 'central';

    protected $table = 'tenants';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'is_suspended' => 'boolean',
    ];

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class, 'tenant_id', 'id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'tenant_id', 'id');
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class, 'tenant_id', 'id')
            ->whereIn('status', ['trial', 'active'])
            ->latestOfMany();
    }
}
