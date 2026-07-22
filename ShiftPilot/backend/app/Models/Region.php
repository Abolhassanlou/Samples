<?php

namespace App\Models;

use Database\Factories\RegionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'parent_id',
    'name',
    'type',
    'code',
    'country_code',
    'timezone',
    'is_active',
])]
class Region extends Model
{
    /** @use HasFactory<RegionFactory> */
    use HasFactory;

    public const TYPE_CITY = 'city';

    public const TYPE_DISTRICT = 'district';

    public const TYPE_STATE = 'state';

    public const TYPE_COUNTRY = 'country';

    public const TYPE_CUSTOM = 'custom';

    public const TYPES = [
        self::TYPE_CITY,
        self::TYPE_DISTRICT,
        self::TYPE_STATE,
        self::TYPE_COUNTRY,
        self::TYPE_CUSTOM,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'parent_id'
        );
    }

    public function children(): HasMany
    {
        return $this->hasMany(
            self::class,
            'parent_id'
        );
    }

    public function employeeRegions(): HasMany
    {
        return $this->hasMany(EmployeeRegion::class);
    }

    public function memberships(): BelongsToMany
    {
        return $this->belongsToMany(
            CompanyMembership::class,
            'employee_regions'
        )
            ->withPivot([
                'status',
                'approved_by_user_id',
                'approved_at',
                'is_active',
            ])
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
