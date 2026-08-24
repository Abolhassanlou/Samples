<?php

namespace Modules\Shift\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A dynamic, company-defined catalog of position types within a shift
 * (e.g. "Driver", "Coordinator", "Team Lead") — deliberately NOT a fixed
 * enum, matching the same philosophy as Employee's Qualification catalog:
 * every company defines its own roles, nothing is hardcoded.
 */
class ShiftRole extends Model
{
    protected $table = 'shift_roles';

    protected $fillable = [
        'name',
        'description',
    ];
}
