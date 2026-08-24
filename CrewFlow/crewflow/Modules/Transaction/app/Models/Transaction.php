<?php

namespace Modules\Transaction\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Authentication\Models\User;
use Modules\Client\Models\Client;
use Modules\Shift\Models\Shift;

/**
 * Money owed BY a Client TO this company — never to be confused with
 * Tenancy's Plan/Subscription (money this company owes the platform,
 * which lives in an entirely separate, soon-to-be-extracted context).
 */
class Transaction extends Model
{
    protected $table = 'transactions';

    protected $fillable = [
        'client_id',
        'shift_id',
        'amount',
        'status',
        'description',
        'due_at',
        'paid_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_at' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
