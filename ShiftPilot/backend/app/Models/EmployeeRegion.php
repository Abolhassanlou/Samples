<?php

namespace App\Models;

use Database\Factories\EmployeeRegionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_membership_id',
    'region_id',
    'status',
    'approved_by_user_id',
    'approved_at',
    'is_active',
])]
class EmployeeRegion extends Model
{
    /** @use HasFactory<EmployeeRegionFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
    ];

    public function companyMembership(): BelongsTo
    {
        return $this->belongsTo(
            CompanyMembership::class
        );
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by_user_id'
        );
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_APPROVED)
            ->where('is_active', true);
    }

    public function isUsable(): bool
    {
        return $this->status === self::STATUS_APPROVED
            && $this->is_active
            && $this->companyMembership->isActive()
            && $this->region->is_active
            && $this->companyMembership->company_id
                === $this->region->company_id;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
