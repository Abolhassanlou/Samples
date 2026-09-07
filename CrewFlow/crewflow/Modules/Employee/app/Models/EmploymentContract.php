<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single contract — a company_worker can have many of these over
 * time (e.g. today Geringfügig, a different contract a few months
 * later). This history is the whole point of splitting Worker from
 * CompanyWorker from EmploymentContract: identity/documents don't get
 * rewritten every time a new contract starts.
 *
 * `event_id` (nullable): some companies need a fresh contract per
 * event/project (typically `work_contract`); others sign one ongoing
 * contract that covers everything (e.g. an ongoing role like teaching —
 * typically `employment_contract`). This one column supports both,
 * rather than building two separate contract systems. Deliberately NOT
 * a belongsTo relationship to Shift's Event model here — Shift already
 * depends on Employee, so an Eloquent relationship the other way would
 * be circular; the frontend fetches event details separately by this id.
 */
class EmploymentContract extends Model
{
    protected $table = 'employment_contracts';

    protected $fillable = [
        'company_worker_id',
        'event_id',
        'contract_number',
        'contract_type',
        'work_time_model',
        'is_marginal',
        'weekly_hours',
        'start_date',
        'end_date',
        'status',
        'file_path',
        'signed_at',
        'termination_date',
        'termination_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_marginal' => 'boolean',
            'weekly_hours' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'signed_at' => 'datetime',
            'termination_date' => 'date',
        ];
    }

    public function companyWorker(): BelongsTo
    {
        return $this->belongsTo(CompanyWorker::class);
    }

    /**
     * Derived, not stored — see the migration's docblock on `end_date`
     * for why a separate duration_type column would risk disagreeing
     * with the actual dates.
     */
    public function isPermanent(): bool
    {
        return $this->end_date === null;
    }

    public function isSigned(): bool
    {
        return $this->signed_at !== null;
    }

    public function hasFile(): bool
    {
        return $this->file_path !== null;
    }

    /**
     * The actual eligibility check Shift's AssignmentController uses:
     * status must be "active", and if there's an end_date, it must not
     * have passed yet.
     */
    public function isCurrentlyActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        return $this->end_date === null || $this->end_date->isFuture() || $this->end_date->isToday();
    }
}
