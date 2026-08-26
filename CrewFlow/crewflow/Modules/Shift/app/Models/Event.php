<?php

namespace Modules\Shift\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Client\Models\Client;
use Modules\Organization\Models\Branch;

/**
 * Groups multiple Shifts under one big occasion sharing a client/location
 * (e.g. a wedding needing separate Shifts for drivers and guards, each
 * with their own precise time window). Optional — a Shift doesn't need
 * an Event to exist.
 */
class Event extends Model
{
    protected $table = 'events';

    protected $fillable = [
        'branch_id',
        'client_id',
        'title',
        'description',
        'location_address',
        'location_lat',
        'location_lng',
        'starts_at',
        'ends_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
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

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function transportGroups(): HasMany
    {
        return $this->hasMany(TransportGroup::class);
    }
}
