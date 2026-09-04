<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Authentication\Models\User;

/**
 * Purely personal/legal facts about the person — NOT their employment
 * relationship or contract (see CompanyWorker/EmploymentContract for
 * that). This split is deliberate: contract terms change over time and
 * have their own history, while a person's identity/documents/work
 * authorization don't get rewritten every time a new contract starts.
 * One-to-one with User.
 */
class Worker extends Model
{
    protected $table = 'workers';

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'date_of_birth',
        'address',
        'postal_code',
        'city',
        'country',
        'status',
        'work_authorization_status',
        'work_authorization_type',
        'work_authorization_expiry_date',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'work_authorization_expiry_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function companyWorker(): HasOne
    {
        return $this->hasOne(CompanyWorker::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(WorkerDocument::class, 'worker_id', 'user_id');
    }

    public function qualifications(): HasMany
    {
        return $this->hasMany(WorkerQualification::class, 'worker_id', 'user_id');
    }

    public function availability(): HasMany
    {
        return $this->hasMany(WorkerAvailability::class, 'worker_id', 'user_id');
    }
}
