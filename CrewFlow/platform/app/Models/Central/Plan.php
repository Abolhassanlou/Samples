<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Read-only, see Company's docblock for why.
 */
class Plan extends Model
{
    protected $connection = 'central';

    protected $table = 'plans';

    public function limits(): HasMany
    {
        return $this->hasMany(PlanLimit::class);
    }
}
