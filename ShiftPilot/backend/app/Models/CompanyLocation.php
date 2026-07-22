<?php

namespace App\Models;

use Database\Factories\CompanyLocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'name',
    'type',
    'code',
    'email',
    'phone',
    'address_line_1',
    'address_line_2',
    'postal_code',
    'city',
    'country_code',
    'timezone',
    'is_active',
])]
class CompanyLocation extends Model
{
    /** @use HasFactory<CompanyLocationFactory> */
    use HasFactory;

    public const TYPE_BRANCH = 'branch';

    public const TYPE_OFFICE = 'office';

    public const TYPE_DEPARTMENT = 'department';

    public const TYPES = [
        self::TYPE_BRANCH,
        self::TYPE_OFFICE,
        self::TYPE_DEPARTMENT,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function membershipAssignments(): HasMany
    {
        return $this->hasMany(
            CompanyMembershipLocation::class
        );
    }

    public function memberships(): BelongsToMany
    {
        return $this->belongsToMany(
            CompanyMembership::class,
            'company_membership_locations'
        )->withTimestamps();
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
