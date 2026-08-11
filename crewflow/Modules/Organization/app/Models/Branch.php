<?php

namespace Modules\Organization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Authentication\Models\User;

class Branch extends Model
{
    protected $table = 'branches';

    protected $fillable = [
        'name',
        'city',
        'is_main',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Dispatchers explicitly scoped to this branch (see UserBranch). Empty
     * relation does NOT mean "no dispatcher can manage this branch" — per
     * the project's access rule, a dispatcher with zero UserBranch rows has
     * unrestricted access to every branch. This relation only lists the
     * dispatchers who have been deliberately restricted to a subset of branches.
     */
    public function scopedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_branch');
    }
}
