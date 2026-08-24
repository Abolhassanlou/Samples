<?php

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Shift\Models\Assignment;

class CompletionProof extends Model
{
    protected $table = 'completion_proofs';

    protected $fillable = [
        'assignment_id',
        'proof_type',
        'file_path',
        'signature_data',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }
}
