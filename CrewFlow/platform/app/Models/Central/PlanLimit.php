<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;

/**
 * Read-only, see Company's docblock for why.
 */
class PlanLimit extends Model
{
    protected $connection = 'central';

    protected $table = 'plan_limits';
}
