<?php

namespace App\Models;

use Database\Factories\FeatureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'key',
    'name',
    'description',
    'category',
    'default_enabled',
    'is_active',
])]
class Feature extends Model
{
    /** @use HasFactory<FeatureFactory> */
    use HasFactory;

    public function companyFeatures(): HasMany
    {
        return $this->hasMany(CompanyFeature::class);
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(
            Company::class,
            'company_features'
        )
            ->withPivot([
                'is_enabled',
                'configuration',
                'enabled_at',
                'expires_at',
                'enabled_by_user_id',
            ])
            ->withTimestamps();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_enabled' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}