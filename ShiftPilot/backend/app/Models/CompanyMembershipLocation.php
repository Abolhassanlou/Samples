<?php

namespace App\Models;

use Database\Factories\CompanyMembershipLocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_membership_id',
    'company_location_id',
])]
class CompanyMembershipLocation extends Model
{
    /** @use HasFactory<CompanyMembershipLocationFactory> */
    use HasFactory;

    public function companyMembership(): BelongsTo
    {
        return $this->belongsTo(
            CompanyMembership::class
        );
    }

    public function companyLocation(): BelongsTo
    {
        return $this->belongsTo(
            CompanyLocation::class
        );
    }
}
