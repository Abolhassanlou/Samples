<?php

namespace Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single limit (e.g. max_workers) attached to a Plan. Each limit has its
 * own enforcement_mode, so a plan can mix hard blocks (e.g. worker count)
 * with soft warnings (e.g. branch count) rather than one rule for everything.
 */
class PlanLimit extends Model
{
    protected $table = 'plan_limits';

    protected $fillable = [
        'plan_id',
        'limit_type',
        'max_value',
        'enforcement_mode',
    ];

    protected function casts(): array
    {
        return [
            'max_value' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
