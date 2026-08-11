<?php

namespace Modules\Setting\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Per-company configurable behavior. This model USED to live in the
 * Tenancy module's Central database (with a tenant_id column, one row
 * per company). It was moved here — tenant-scoped, one row per tenant
 * DATABASE, no tenant_id column needed at all — per the decision to
 * fully separate platform (Central) concerns from product (tenant)
 * concerns. See project-business-model.md, section 7.
 */
class CompanySettings extends Model
{
    protected $table = 'company_settings';

    protected $fillable = [
        'default_recurrence_mode',
        'shift_completion_mode',
        'shift_visibility_mode',
        'warning_hour_threshold',
        'warning_income_threshold',
        'gps_checkin_required',
    ];

    protected function casts(): array
    {
        return [
            'warning_hour_threshold' => 'integer',
            'warning_income_threshold' => 'decimal:2',
            'gps_checkin_required' => 'boolean',
        ];
    }

    /**
     * There is always exactly one settings row per tenant database.
     * Creates it with sensible defaults on first access if it doesn't
     * exist yet (e.g. a company registered before this module existed).
     */
    public static function current(): self
    {
        return static::firstOrCreate([], [
            'default_recurrence_mode' => 'reconfirm_each_time',
            'shift_completion_mode' => 'button_confirm',
            'shift_visibility_mode' => 'show_disabled',
            'gps_checkin_required' => false,
        ]);
    }
}
