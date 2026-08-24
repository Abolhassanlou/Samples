<?php

namespace Modules\Authentication\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Base User for every tenant.
 *
 * Important architectural note: this model is intentionally kept lean and
 * only holds authentication-related fields. Worker-specific fields such as
 * employment_type, hourly_rate, and branch_id live in the Workforce module
 * (a WorkerProfile model with a one-to-one relation to this User). Reason:
 * if those fields lived here, the Authentication module would be forced to
 * depend on the Organization module (for Branch), which would violate the
 * project's layered dependency direction (Authentication should stay close
 * to layer zero, with minimal dependencies of its own).
 */
class User extends Authenticatable
{
    use HasApiTokens, HasRoles, Notifiable;

    /**
     * Default guard for spatie/laravel-permission.
     * Since this project is SPA+API, all roles/permissions are defined on the api guard.
     */
    protected $guard_name = 'api';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
