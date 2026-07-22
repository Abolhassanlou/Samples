<?php

namespace App\Models;

use Database\Factories\CompanyFeatureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_id',
    'feature_id',
    'is_enabled',
    'configuration',
    'enabled_at',
    'expires_at',
    'enabled_by_user_id',
])]
class CompanyFeature extends Model
{
    /** @use HasFactory<CompanyFeatureFactory> */
    use HasFactory;

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }

    public function enabledBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'enabled_by_user_id'
        );
    }

    public function isAvailable(): bool
    {
        if (! $this->is_enabled) {
            return false;
        }

        if (
            $this->enabled_at
            && $this->enabled_at->isFuture()
        ) {
            return false;
        }

        if (
            $this->expires_at
            && ! $this->expires_at->isFuture()
        ) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'configuration' => 'array',
            'enabled_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}