<?php

namespace Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-company configurable behavior. Lives in the Central database
 * (one row per Company), NOT inside the tenant's own database, since
 * these settings need to be readable before tenancy is even initialized
 * (e.g. to decide behavior for a request before switching DB connection).
 */
class CompanySettings extends Model
{
    protected $table = 'company_settings';

    protected $fillable = [
        'tenant_id',
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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'tenant_id', 'id');
    }
}
