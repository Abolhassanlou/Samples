<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Authentication\Models\User;

/**
 * A qualification actually granted to a worker. Per the project's rule,
 * granting ALWAYS requires a human admin action — never automatic just
 * because a document of the right type was uploaded (see `source`).
 */
class WorkerQualification extends Model
{
    protected $table = 'worker_qualifications';

    protected $fillable = [
        'worker_id',
        'qualification_id',
        'source', // document_verified | company_granted
        'supporting_document_id',
        'granted_by',
        'granted_at',
    ];

    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
        ];
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function qualification(): BelongsTo
    {
        return $this->belongsTo(Qualification::class);
    }

    public function supportingDocument(): BelongsTo
    {
        return $this->belongsTo(WorkerDocument::class, 'supporting_document_id');
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
