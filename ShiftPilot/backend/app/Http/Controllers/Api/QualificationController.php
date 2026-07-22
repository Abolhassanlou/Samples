<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Qualification\StoreQualificationRequest;
use App\Http\Requests\Qualification\UpdateQualificationRequest;
use App\Models\Company;
use App\Models\Qualification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class QualificationController extends Controller
{
    public function index(Company $company): JsonResponse
    {
        Gate::authorize(
            'viewAnyForCompany',
            [Qualification::class, $company]
        );

        return response()->json([
            'data' => [
                'qualifications' => $company
                    ->qualifications()
                    ->orderBy('type')
                    ->orderBy('name')
                    ->get(),
            ],
        ]);
    }

    public function store(
        StoreQualificationRequest $request,
        Company $company
    ): JsonResponse {
        Gate::authorize(
            'createForCompany',
            [Qualification::class, $company]
        );

        $qualification = $company
            ->qualifications()
            ->create($request->validated());

        return response()->json([
            'message' => 'Qualification created successfully.',
            'data' => [
                'qualification' => $qualification,
            ],
        ], Response::HTTP_CREATED);
    }

    public function show(
        Qualification $qualification
    ): JsonResponse {
        Gate::authorize('view', $qualification);

        return response()->json([
            'data' => [
                'qualification' => $qualification,
            ],
        ]);
    }

    public function update(
        UpdateQualificationRequest $request,
        Qualification $qualification
    ): JsonResponse {
        Gate::authorize('update', $qualification);

        $qualification->update(
            $request->validated()
        );

        return response()->json([
            'message' => 'Qualification updated successfully.',
            'data' => [
                'qualification' => $qualification->refresh(),
            ],
        ]);
    }

    public function destroy(
        Qualification $qualification
    ): Response {
        Gate::authorize('delete', $qualification);

        $qualification->delete();

        return response()->noContent();
    }
}
