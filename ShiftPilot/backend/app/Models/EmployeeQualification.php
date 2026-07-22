<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Database\Factories\EmployeeQualificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_membership_id',
    'qualification_id',
    'level',
    'status',
    'issued_at',
    'expires_at',
    'verified_by_user_id',
    'verified_at',
    'notes',
])]
class EmployeeQualification extends Model
{
    /** @use HasFactory<EmployeeQualificationFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_VERIFIED,
        self::STATUS_REJECTED,
        self::STATUS_EXPIRED,
    ];

    public function companyMembership(): BelongsTo
    {
        return $this->belongsTo(
            CompanyMembership::class
        );
    }

    public function qualification(): BelongsTo
    {
        return $this->belongsTo(
            Qualification::class
        );
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'verified_by_user_id'
        );
    }

    public function scopeUsableOn(
        Builder $query,
        CarbonInterface|string $date
    ): Builder {
        $dateValue = is_string($date)
            ? $date
            : $date->toDateString();

        return $query
            ->where('status', self::STATUS_VERIFIED)
            ->where(function (Builder $query) use (
                $dateValue
            ): void {
                $query
                    ->whereNull('expires_at')
                    ->orWhereDate(
                        'expires_at',
                        '>=',
                        $dateValue
                    );
            });
    }

    public function isUsableOn(
        CarbonInterface|string $date
    ): bool {
        $dateValue = is_string($date)
            ? Carbon::parse($date)
            : $date;

        if (
            $this->status !== self::STATUS_VERIFIED
            || ! $this->companyMembership->isActive()
            || ! $this->qualification->is_active
            || $this->companyMembership->company_id
                !== $this->qualification->company_id
        ) {
            return false;
        }

        if (
            $this->qualification->requires_expiry_date
            && ! $this->expires_at
        ) {
            return false;
        }

        return ! $this->expires_at
            || ! $this->expires_at->isBefore($dateValue);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'expires_at' => 'date',
            'verified_at' => 'datetime',
        ];
    }
}
