<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * A user of the platform itself (you, the SaaS owner) — completely
 * independent from crewflow now. Lives in this project's OWN database
 * (see config/database.php's default 'mysql' connection), not in
 * crewflow's Central database at all. Roles/permissions are fully
 * dynamic (spatie/laravel-permission, installed fresh in this project —
 * no longer shared with crewflow's tenant-side Role/Permission classes).
 */
class PlatformUser extends Authenticatable
{
    use HasApiTokens, HasRoles, Notifiable;

    protected $table = 'platform_users';

    protected $guard_name = 'api';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
