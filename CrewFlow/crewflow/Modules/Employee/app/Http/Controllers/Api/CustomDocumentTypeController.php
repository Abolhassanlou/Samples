<?php

namespace Modules\Employee\Http\Controllers\Api;

use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Employee\Http\Requests\CustomDocumentTypeRequest;
use Modules\Employee\Http\Resources\CustomDocumentTypeResource;
use Modules\Employee\Models\CustomDocumentType;

/**
 * Company-added document types, extending (never replacing) the fixed
 * baseline list in WorkerDocumentController. Viewing is open — a worker
 * needs the full list (baseline + custom) to pick from when uploading.
 */
class CustomDocumentTypeController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = CustomDocumentType::query()->orderBy('sort_order');

        // Same fix as CustomFieldDefinitionController::index() —
        // $request->string() returns a Stringable OBJECT, not a plain
        // string, which can fail to bind/match correctly as an Eloquent
        // where() value. $request->query() returns the raw string.
        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        if (! $request->user()->can('users.manage')) {
            $query->where('is_active', true);
        }

        return $this->success(CustomDocumentTypeResource::collection($query->get()));
    }

    public function store(CustomDocumentTypeRequest $request)
    {
        $type = CustomDocumentType::create($request->validated());

        return $this->success(new CustomDocumentTypeResource($type), 'Document type created', 201);
    }

    public function update(CustomDocumentTypeRequest $request, CustomDocumentType $documentType)
    {
        $documentType->update($request->validated());

        return $this->success(new CustomDocumentTypeResource($documentType), 'Document type updated');
    }

    /**
     * A real, permanent delete. Safer than deleting a CustomFieldDefinition
     * (see that controller's destroy()) — WorkerDocument.document_type is
     * a plain string, not a foreign key, so removing this type definition
     * does NOT touch any files/records already uploaded under it; they
     * just keep a document_type value that no longer maps to an active
     * type. Prefer disabling (PUT { is_active: false }) to keep the type
     * choosable-again later without recreating it from scratch.
     */
    public function destroy(CustomDocumentType $documentType)
    {
        $documentType->delete();

        return $this->success(null, 'Document type deleted');
    }
}
