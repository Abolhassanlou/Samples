<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\AvailabilityOverrideFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_membership_id',
    'date',
    'start_time',
    'end_time',
    'status',
    'timezone',
    'note',
])]
class AvailabilityOverride extends Model
{
    /** @use HasFactory<AvailabilityOverrideFactory> */
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

    public function scopeForDate(
        Builder $query,
        CarbonInterface|string $date
    ): Builder {
        $dateValue = is_string($date)
            ? $date
            : $date->toDateString();

        return $query->whereDate('date', $dateValue);
    }

    public function appliesToWholeDay(): bool
    {
        return $this->start_time === null
            && $this->end_time === null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}