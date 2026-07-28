<?php

namespace Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * Company is this project's tenant model. It replaces the package's default
 * Tenant model (see config/tenancy.php -> 'tenant_model').
 *
 * It lives in the Central database. `id` is a UUID (generated automatically
 * by the package). `company_code` is the human-friendly code workers/staff
 * enter at registration time to identify which company (tenant) they
 * belong to (see Modules\Core\Http\Controllers\Api\AuthController::register).
 */
class Company extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $table = 'tenants';

    protected $fillable = [
        'id',
        'company_code',
        'name',
    ];

    /**
     * Columns listed here are real, queryable database columns.
     * Any other attribute set on this model falls back to being stored
     * inside the `data` JSON column (provided by the package's VirtualColumn trait).
     * `id` must always be included here.
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'company_code',
            'name',
        ];
    }

    public function settings(): HasOne
    {
        return $this->hasOne(CompanySettings::class, 'tenant_id', 'id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'tenant_id', 'id');
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class, 'tenant_id', 'id')
            ->where('status', 'active')
            ->latestOfMany();
    }
}
