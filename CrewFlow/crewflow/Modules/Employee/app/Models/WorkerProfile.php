<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Authentication\Models\User;
use Modules\Organization\Models\Branch;

/**
 * Worker-specific fields (employment_type, hourly_rate, home branch) live
 * here — not on Authentication's User — specifically so Authentication
 * doesn't need to depend on Organization (for Branch). One-to-one with User.
 */
class WorkerProfile extends Model
{
    protected $table = 'worker_profiles';

    protected $fillable = [
        'user_id',
        'employment_type',
        'hourly_rate',
        'home_branch_id',
    ];

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function homeBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'home_branch_id');
    }

    public function qualifications(): HasMany
    {
        return $this->hasMany(WorkerQualification::class, 'worker_id', 'user_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(WorkerDocument::class, 'worker_id', 'user_id');
    }

    public function availability(): HasMany
    {
        return $this->hasMany(WorkerAvailability::class, 'worker_id', 'user_id');
    }
}
