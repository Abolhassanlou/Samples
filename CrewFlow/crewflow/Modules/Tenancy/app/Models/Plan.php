<?php

namespace Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $table = 'plans';

    protected $fillable = [
        'name',
        'price',
        'billing_cycle',
        'enabled_features',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'enabled_features' => 'array',
        ];
    }

    public function limits(): HasMany
    {
        return $this->hasMany(PlanLimit::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
