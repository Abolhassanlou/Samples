<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Availability\StoreAvailabilityOverrideRequest;
use App\Http\Requests\Availability\UpdateAvailabilityOverrideRequest;
use App\Models\AvailabilityOverride;
use App\Models\CompanyMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class AvailabilityOverrideController extends Controller
{
    public function index(
        CompanyMembership $companyMembership
    ): JsonResponse {
        Gate::authorize(
            'viewForMembership',
            [AvailabilityOverride::class, $companyMembership]
        );

        $overrides = $companyMembership
            ->availabilityOverrides()
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'data' => [
                'availability_overrides' => $overrides,
            ],
        ]);
    }

    public function store(
        StoreAvailabilityOverrideRequest $request,
        CompanyMembership $companyMembership
    ): JsonResponse {
        Gate::authorize(
            'createForMembership',
            [AvailabilityOverride::class, $companyMembership]
        );

        $availabilityOverride = $companyMembership
            ->availabilityOverrides()
            ->create($request->validated());

        return response()->json([
            'message' => 'Availability override created successfully.',
            'data' => [
                'availability_override' => $availabilityOverride,
            ],
        ], Response::HTTP_CREATED);
    }

    public function show(
        AvailabilityOverride $availabilityOverride
    ): JsonResponse {
        Gate::authorize('view', $availabilityOverride);

        return response()->json([
            'data' => [
                'availability_override' => $availabilityOverride,
            ],
        ]);
    }

    public function update(
        UpdateAvailabilityOverrideRequest $request,
        AvailabilityOverride $availabilityOverride
    ): JsonResponse {
        Gate::authorize('update', $availabilityOverride);

        $availabilityOverride->update($request->validated());

        return response()->json([
            'message' => 'Availability override updated successfully.',
            'data' => [
                'availability_override' => $availabilityOverride->refresh(),
            ],
        ]);
    }

    public function destroy(
        AvailabilityOverride $availabilityOverride
    ): Response {
        Gate::authorize('delete', $availabilityOverride);

        $availabilityOverride->delete();

        return response()->noContent();
    }
}
