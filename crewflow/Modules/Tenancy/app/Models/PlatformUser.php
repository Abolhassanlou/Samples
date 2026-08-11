<?php

namespace Modules\Tenancy\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * A user of the platform itself (you, as the SaaS owner — not a company's
 * own staff). Deliberately a completely separate model from
 * Modules\Authentication\Models\User: different table, always Central (never a
 * tenant database).
 *
 * Roles/permissions ARE fully dynamic here too, reusing the exact same
 * Modules\Authorization\Models\Role / Spatie\Permission\Models\Permission classes
 * tenant users use — Central and every tenant are just physically separate
 * databases with an identical `roles`/`permissions` schema, so the same
 * model classes work for both. The `guard_name` column ('central' here,
 * vs 'api' for tenant users) is what keeps the two worlds' roles from
 * ever being mixed up, even though they share model classes.
 */
class PlatformUser extends Authenticatable
{
    use HasApiTokens, HasRoles, Notifiable;

    protected $table = 'platform_users';

    protected $guard_name = 'central';

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

    /**
     * Force this model to always use the Central connection, even if
     * called from inside code that has tenancy initialized. Platform
     * users only ever exist centrally.
     */
    public function getConnectionName(): ?string
    {
        return config('tenancy.database.central_connection');
    }
}
