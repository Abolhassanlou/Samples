<?php

namespace Modules\Shift\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Client\Models\Client;
use Modules\Organization\Models\Branch;

/**
 * A posted piece of work a company needs filled. MVP scope — no Event
 * grouping, no TransportGroup, no ShiftQualification yet (see module.json
 * for what's deferred).
 */
class Shift extends Model
{
    protected $table = 'shifts';

    protected $fillable = [
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

    public function confirmedAssignmentsCount(): int
    {
        return $this->assignments()->where('status', 'confirmed')->count();
    }

    public function isFull(): bool
    {
        return $this->confirmedAssignmentsCount() >= $this->quantity_needed;
    }
}
