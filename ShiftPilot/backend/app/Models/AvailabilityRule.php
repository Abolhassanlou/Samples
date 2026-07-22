<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\AvailabilityRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_membership_id',
    'weekday',
    'start_time',
    'end_time',
    'status',
    'valid_from',
    'valid_until',
    'timezone',
    'is_active',
])]
class AvailabilityRule extends Model
{
    /** @use HasFactory<AvailabilityRuleFactory> */
    use HasFactory;

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_PREFERRED = 'preferred';

    public const STATUS_UNAVAILABLE = 'unavailable';

    public const STATUSES = [
        self::STATUS_AVAILABLE,
        self::STATUS_PREFERRED,
        self::STATUS_UNAVAILABLE,
    ];

    public function companyMembership(): BelongsTo
    {
        return $this->belongsTo(CompanyMembership::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeEffectiveOn(
        Builder $query,
        CarbonInterface|string $date
    ): Builder {
        $dateValue = is_string($date)
            ? $date
            : $date->toDateString();

        return $query
            ->where(function (Builder $query) use ($dateValue): void {
                $query
                    ->whereNull('valid_from')
                    ->orWhereDate('valid_from', '<=', $dateValue);
            })
            ->where(function (Builder $query) use ($dateValue): void {
                $query
                    ->whereNull('valid_until')
                    ->orWhereDate('valid_until', '>=', $dateValue);
            });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'is_active' => 'boolean',
        ];
    }
}