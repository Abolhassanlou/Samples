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

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
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
}
