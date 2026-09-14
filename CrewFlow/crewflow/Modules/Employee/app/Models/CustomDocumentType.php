<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A company-added document type, e.g. "Vaccination card" — shown
 * alongside the fixed baseline list (identity_document, work_permit_front,
 * etc. — see WorkerDocumentController) in the upload dropdown. Never
 * replaces the baseline, only extends it. `category` slots it into
 * either the "My info" personal-documents list or the top-level
 * "Documents" (work-related) list, same split as the fixed baseline.
 */
class CustomDocumentType extends Model
{
    protected $table = 'custom_document_types';

    protected $fillable = [
        'category',
        'key',
        'label',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
