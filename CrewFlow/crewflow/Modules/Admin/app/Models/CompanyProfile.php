<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * This company's own profile — its name, logo, and contact info, as the
 * Company Admin wants it displayed (e.g. on invoices, in the worker app
 * header). Same singleton pattern as Setting's CompanySettings: exactly
 * one row per tenant database, no tenant_id column needed.
 */
class CompanyProfile extends Model
{
    protected $table = 'company_profiles';

    protected $fillable = [
        'display_name',
        'logo_path',
        'address',
        'phone',
        'email',
        'website',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([]);
    }
}
