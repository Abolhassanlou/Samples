<?php

namespace Modules\Shift\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * "This shift needs 2 of role X" — entirely optional. A Shift with zero
 * positions works exactly as before (generic quantity_needed, no role
 * distinction) for backward compatibility with every Shift created
 * before this feature existed.
 */
class ShiftPosition extends Model
{
    protected $table = 'shift_positions';

    protected $fillable = [
        'shift_id',
        'shift_role_id',
        'quantity_needed',
        'hourly_rate', // nullable — overrides the parent Shift's hourly_rate for this position, if set
    ];

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(ShiftRole::class, 'shift_role_id');
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
