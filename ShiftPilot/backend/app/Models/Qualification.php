<?php

namespace App\Models;

use Database\Factories\QualificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'name',
    'type',
    'code',
    'description',
    'requires_verification',
    'requires_expiry_date',
    'is_active',
])]
class Qualification extends Model
{
    /** @use HasFactory<QualificationFactory> */
    use HasFactory;

    public const TYPE_SKILL = 'skill';

    public const TYPE_SUBJECT = 'subject';

    public const TYPE_LANGUAGE = 'language';

    public const TYPE_CERTIFICATE = 'certificate';

    public const TYPE_TRAINING = 'training';

    public const TYPES = [
        self::TYPE_SKILL,
        self::TYPE_SUBJECT,
        self::TYPE_LANGUAGE,
        self::TYPE_CERTIFICATE,
        self::TYPE_TRAINING,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employeeQualifications(): HasMany
    {
        return $this->hasMany(
            EmployeeQualification::class
        );
    }

    public function memberships(): BelongsToMany
    {
        return $this->belongsToMany(
            CompanyMembership::class,
            'employee_qualifications'
        )
            ->withPivot([
                'level',
                'status',
                'issued_at',
                'expires_at',
                'verified_by_user_id',
                'verified_at',
                'notes',
            ])
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requires_verification' => 'boolean',
            'requires_expiry_date' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
