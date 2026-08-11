<?php

namespace Modules\Employee\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Employee\Http\Requests\QualificationRequest;
use Modules\Employee\Http\Resources\QualificationResource;
use Modules\Employee\Models\Qualification;

/**
 * Authorization for mutating actions handled at the route level
 * (permission:qualifications.manage). Viewing the catalog only requires
 * being an authenticated company user.
 */
class QualificationController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->success(QualificationResource::collection(Qualification::orderBy('name')->get()));
    }

    public function store(QualificationRequest $request)
    {
        $qualification = Qualification::create($request->validated());

        return $this->success(new QualificationResource($qualification), 'Qualification created', 201);
    }

    public function update(QualificationRequest $request, Qualification $qualification)
    {
        $qualification->update($request->validated());

        return $this->success(new QualificationResource($qualification), 'Qualification updated');
    }

    public function destroy(Qualification $qualification)
    {
        $qualification->delete();

        return $this->success(null, 'Qualification deleted');
    }
}
