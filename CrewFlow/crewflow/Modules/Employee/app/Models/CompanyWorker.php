<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Organization\Models\Branch;

/**
 * The employment RELATIONSHIP between a Worker and this company —
 * deliberately separate from Worker (personal facts) and
 * EmploymentContract (the specific, historical contract terms). No
 * `company_id` column: this database already belongs to exactly one
 * company (stancl/tenancy), so that column would be redundant — see
 * this module's README for the full reasoning.
 */
class CompanyWorker extends Model
{
    protected $table = 'company_workers';

    protected $fillable = [
        'worker_id',
        'employee_number',
        'home_branch_id',
        'works_night_shifts',
        'status',
        'invitation_token',
        'invitation_expires_at',
        'joined_at',
        'left_at',
    ];

    protected function casts(): array
    {
        return [
            'works_night_shifts' => 'boolean',
            'invitation_expires_at' => 'datetime',
            'joined_at' => 'date',
            'left_at' => 'date',
        ];
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function homeBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'home_branch_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(EmploymentContract::class);
    }

    public function activeContract(): HasMany
    {
        return $this->contracts()->where('status', 'active');
    }
}
