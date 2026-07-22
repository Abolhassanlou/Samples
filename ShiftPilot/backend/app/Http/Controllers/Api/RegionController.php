<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Region\StoreRegionRequest;
use App\Http\Requests\Region\UpdateRegionRequest;
use App\Models\Company;
use App\Models\Region;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class RegionController extends Controller
{
    public function index(Company $company): JsonResponse
    {
        Gate::authorize(
            'viewAnyForCompany',
            [Region::class, $company]
        );

        return response()->json([
            'data' => [
                'regions' => $company
                    ->regions()
                    ->orderBy('type')
                    ->orderBy('name')
                    ->get(),
            ],
        ]);
    }

    public function store(
        StoreRegionRequest $request,
        Company $company
    ): JsonResponse {
        Gate::authorize(
            'createForCompany',
            [Region::class, $company]
        );

        $region = $company
            ->regions()
            ->create($request->validated());

        return response()->json([
            'message' => 'Region created successfully.',
            'data' => [
                'region' => $region,
            ],
        ], Response::HTTP_CREATED);
    }

    public function show(Region $region): JsonResponse
    {
        Gate::authorize('view', $region);

        return response()->json([
            'data' => [
                'region' => $region,
            ],
        ]);
    }

    public function update(
        UpdateRegionRequest $request,
        Region $region
    ): JsonResponse {
        Gate::authorize('update', $region);

        $region->update($request->validated());

        return response()->json([
            'message' => 'Region updated successfully.',
            'data' => [
                'region' => $region->refresh(),
            ],
        ]);
    }

    public function destroy(Region $region): Response
    {
        Gate::authorize('delete', $region);

        $region->delete();

        return response()->noContent();
    }
}
