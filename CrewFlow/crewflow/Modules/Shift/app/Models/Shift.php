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
 * `qualification_override`: an escape hatch for staffing shortages — e.g.
 * an unpopular night shift nobody with the right qualification wants.
 * When true, ShiftVisibility skips the qualification check entirely for
 * this Shift (branch/event access is still required); everyone who can
 * already see the shift for access reasons sees it regardless of whether
 * they hold the qualifications it lists.
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
        'qualification_override',
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
            'qualification_override' => 'boolean',
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

    public function positions(): HasMany
    {
        return $this->hasMany(ShiftPosition::class);
    }

    public function requiredQualifications(): HasMany
    {
        return $this->hasMany(ShiftQualification::class);
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
