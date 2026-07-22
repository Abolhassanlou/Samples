<?php

namespace App\Models;

use Database\Factories\CompanyMembershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'user_id',
    'role',
    'status',
    'joined_at',
    'primary_company_location_id',
    'access_all_locations',
    'all_regions',
])]
class CompanyMembership extends Model
{
    /** @use HasFactory<CompanyMembershipFactory> */
    use HasFactory;

    public const ROLE_COMPANY_ADMIN = 'company_admin';

    public const ROLE_DISPATCHER = 'dispatcher';

    public const ROLE_EMPLOYEE = 'employee';

    public const STATUS_INVITED = 'invited';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function primaryLocation(): BelongsTo
    {
        return $this->belongsTo(
            CompanyLocation::class,
            'primary_company_location_id'
        );
    }

    public function locationAssignments(): HasMany
    {
        return $this->hasMany(
            CompanyMembershipLocation::class
        );
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(
            CompanyLocation::class,
            'company_membership_locations'
        )->withTimestamps();
    }

    /**
     * @return HasMany<AvailabilityRule, $this>
     */
    public function availabilityRules(): HasMany
    {
        return $this->hasMany(AvailabilityRule::class);
    }

    /**
     * @return HasMany<AvailabilityOverride, $this>
     */
    public function availabilityOverrides(): HasMany
    {
        return $this->hasMany(AvailabilityOverride::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function canAccessLocation(
        CompanyLocation $companyLocation
    ): bool {
        if (
            ! $this->isActive()
            || $this->company_id !== $companyLocation->company_id
            || ! $companyLocation->is_active
        ) {
            return false;
        }
        if (
            $this->role === self::ROLE_COMPANY_ADMIN
            || $this->access_all_locations
        ) {
            return true;
        }

        return $this->locations()
            ->whereKey($companyLocation->id)
            ->exists();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'access_all_locations' => 'boolean',
            'all_regions' => 'boolean',
        ];
    }
}
