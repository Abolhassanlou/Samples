<?php

namespace Modules\Employee\Http\Controllers\Api;

use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Employee\Http\Requests\CustomFieldDefinitionRequest;
use Modules\Employee\Http\Resources\CustomFieldDefinitionResource;
use Modules\Employee\Models\CustomFieldDefinition;

/**
 * The company-configurable "questions" a worker answers on their
 * profile (Personal details / Skills). Viewing is open to any
 * authenticated user — a worker needs to see the question list to
 * answer it — managing needs users.manage, same permission as editing
 * a worker's own profile fields.
 */
class CustomFieldDefinitionController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = CustomFieldDefinition::query()->orderBy('sort_order');

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        // Workers answering questions only need the active ones; an
        // admin managing the list wants to see everything, including
        // ones they've turned off.
        if (! $request->user()->can('users.manage')) {
            $query->where('is_active', true);
        }

        return $this->success(CustomFieldDefinitionResource::collection($query->get()));
    }

    public function store(CustomFieldDefinitionRequest $request)
    {
        $field = CustomFieldDefinition::create($request->validated());

        return $this->success(new CustomFieldDefinitionResource($field), 'Field created', 201);
    }

    public function update(CustomFieldDefinitionRequest $request, CustomFieldDefinition $customField)
    {
        $customField->update($request->validated());

        return $this->success(new CustomFieldDefinitionResource($customField), 'Field updated');
    }
}
