<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'slug',
    'company_code',
    'email',
    'phone',
    'timezone',
    'locale',
    'is_active',
])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, SoftDeletes;

    public function memberships(): HasMany
    {
        return $this->hasMany(CompanyMembership::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'company_memberships'
        )
            ->withPivot([
                'role',
                'status',
                'joined_at',
            ])
            ->withTimestamps();
    }

    public function featureAssignments(): HasMany
    {
        return $this->hasMany(CompanyFeature::class);
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(
            Feature::class,
            'company_features'
        )
            ->withPivot([
                'is_enabled',
                'configuration',
                'enabled_at',
                'expires_at',
                '_at',
                'enabled_by_user_id',
            ])
            ->withTimestamps();
    }

    public function hasFeature(string $key): bool
    {
        $feature = Feature::query()
            ->where('key', $key)
            ->where('is_active', true)
            ->first();

        if (! $feature) {
            return false;
        }

        $assignment = $this->featureAssignments()
            ->where('feature_id', $feature->id)
            ->first();

        if (! $assignment) {
            return $feature->default_enabled;
        }

        return $assignment->isAvailable();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }
}