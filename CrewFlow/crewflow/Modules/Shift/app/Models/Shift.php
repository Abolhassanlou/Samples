<?php

namespace Modules\Shift\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Client\Models\Client;
use Modules\Organization\Models\Branch;

/**
 * A posted piece of work a company needs filled. Optionally belongs to
 * an Event (grouping) and/or has role-specific ShiftPosition breakdowns
 * — both fully optional, so every Shift created before these existed
 * keeps working exactly as before (plain quantity_needed, no roles).
 *
 * `qualification_policy`: three states, not a single override flag —
 * `strict` (default, the normal ShiftVisibility hide-rule), `override`
 * (a deliberate staffing-shortage escape hatch — bypasses the
 * qualification check entirely, no warning, everyone sees it), or
 * `warn` (also visible to everyone regardless of qualification, and
 * they can act — but a dispatcher reviewing that worker's interest/
 * assignment sees a flag that this specific one doesn't actually meet
 * the requirement — see `ShiftVisibility::workerQualifies()`).
 */
class Shift extends Model
{
    protected $table = 'shifts';

    protected $fillable = [
        'event_id',
        'branch_id',
        'client_id',
        'title',
        'description',
        'location_type',
        'location_address',
        'location_lat',
        'location_lng',
        'client_contact_name',
        'client_contact_phone',
        'internal_contact_name',
        'internal_contact_phone',
        'quantity_needed',
        'rate_type',
        'hourly_rate',
        'fixed_amount',
        'client_billing_rate',
        'starts_at',
        'ends_at',
        'status',
        'qualification_policy',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'hourly_rate' => 'decimal:2',
            'fixed_amount' => 'decimal:2',
            'client_billing_rate' => 'decimal:2',
            'location_lat' => 'decimal:7',
            'location_lng' => 'decimal:7',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function interests(): HasMany
    {
        return $this->hasMany(ShiftInterest::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    /**
     * Just the confirmed ones — eager-loadable with .worker, unlike
     * confirmedAssignmentsCount() below (a plain query, not a relation).
     * Used so the admin Shifts list can show who's actually confirmed
     * for each shift without a click-through per row.
     */
    public function confirmedAssignments(): HasMany
    {
        return $this->assignments()->where('status', 'confirmed');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(ShiftPosition::class);
    }

    public function requiredQualifications(): HasMany
    {
        return $this->hasMany(ShiftQualification::class);
    }

    /**
     * Only the shift-wide requirements (shift_position_id IS NULL) — the
     * only kind that applies to a Shift with no positions at all. See
     * ShiftPosition::requiredQualifications() for the per-role kind.
     */
    public function shiftLevelQualifications(): HasMany
    {
        return $this->requiredQualifications()->whereNull('shift_position_id');
    }

    public function hasPositions(): bool
    {
        return $this->positions()->exists();
    }

    public function confirmedAssignmentsCount(): int
    {
        return $this->assignments()->where('status', 'confirmed')->count();
    }

    /**
     * When this Shift has role-specific positions, it's full only once
     * EVERY position is full. Otherwise, falls back to the plain
     * quantity_needed count (legacy behavior).
     */
    public function isFull(): bool
    {
        if ($this->hasPositions()) {
            return $this->positions()->get()->every(fn (ShiftPosition $position) => $position->isFull());
        }

        return $this->confirmedAssignmentsCount() >= $this->quantity_needed;
    }
}
