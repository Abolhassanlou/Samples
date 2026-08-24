<?php

namespace Modules\Authorization\Models;

use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Custom role built on top of the spatie/laravel-permission model,
 * adding an is_system field to protect critical roles (e.g. the default
 * "Company Admin" role) from accidental deletion.
 *
 * To activate this model instead of the package's default one, in
 * config/permission.php:
 *   'models' => ['role' => \Modules\Authorization\Models\Role::class]
 */
class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }
}
