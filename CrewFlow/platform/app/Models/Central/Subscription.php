<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Read-only, see Company's docblock for why.
 */
class Subscription extends Model
{
    protected $connection = 'central';

    protected $table = 'subscriptions';

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
