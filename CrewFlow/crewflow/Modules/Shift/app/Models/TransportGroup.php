<?php

namespace Modules\Shift\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * One vehicle for an Event, with a designated driver (that driver's own
 * Assignment — typically to a "Driver" ShiftRole position) and a list of
 * other workers (Assignments) riding along. Event-level, per the
 * business-model doc: a single Event might need several vehicles.
 */
class TransportGroup extends Model
{
    protected $table = 'transport_groups';

    protected $fillable = [
        'event_id',
        'driver_assignment_id',
        'vehicle_description',
        'notes',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function driverAssignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class, 'driver_assignment_id');
    }

    public function passengerAssignments(): BelongsToMany
    {
        return $this->belongsToMany(
            Assignment::class,
            'transport_group_passengers',
            'transport_group_id',
            'assignment_id'
        );
    }
}
