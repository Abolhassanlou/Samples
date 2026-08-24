<?php

namespace Modules\Transaction\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Client\Models\Client;

/**
 * A standing/fixed billing arrangement with a Client — e.g. a retainer or
 * fixed periodic fee, independent of any specific completed Shift. This
 * is separate from the automatic per-shift billing (WorkLogObserver);
 * a client can have both at once if that's how their contract works.
 */
class RecurringBillingProfile extends Model
{
    protected $table = 'recurring_billing_profiles';

    protected $fillable = [
        'client_id',
        'amount',
        'cycle',
        'next_billing_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'next_billing_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function advanceNextBillingDate(): void
    {
        $this->update([
            'next_billing_date' => $this->cycle === 'weekly'
                ? $this->next_billing_date->copy()->addWeek()
                : $this->next_billing_date->copy()->addMonth(),
        ]);
    }
}
