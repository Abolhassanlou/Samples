<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Availability\StoreAvailabilityRuleRequest;
use App\Http\Requests\Availability\UpdateAvailabilityRuleRequest;
use App\Models\AvailabilityRule;
use App\Models\CompanyMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class AvailabilityRuleController extends Controller
{
    public function index(
        CompanyMembership $companyMembership
    ): JsonResponse {
        Gate::authorize(
            'viewForMembership',
            [AvailabilityRule::class, $companyMembership]
        );

        $rules = $companyMembership
            ->availabilityRules()
            ->orderBy('weekday')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'data' => [
                'availability_rules' => $rules,
            ],
        ]);
    }

    public function store(
        StoreAvailabilityRuleRequest $request,
        CompanyMembership $companyMembership
    ): JsonResponse {
        Gate::authorize(
            'createForMembership',
            [AvailabilityRule::class, $companyMembership]
        );

        $availabilityRule = $companyMembership
            ->availabilityRules()
            ->create($request->validated());

        return response()->json([
            'message' => 'Availability rule created successfully.',
            'data' => [
                'availability_rule' => $availabilityRule,
            ],
        ], Response::HTTP_CREATED);
    }

    public function show(
        AvailabilityRule $availabilityRule
    ): JsonResponse {
        Gate::authorize('view', $availabilityRule);

        return response()->json([
            'data' => [
                'availability_rule' => $availabilityRule,
            ],
        ]);
    }

    public function update(
        UpdateAvailabilityRuleRequest $request,
        AvailabilityRule $availabilityRule
    ): JsonResponse {
        Gate::authorize('update', $availabilityRule);

        $availabilityRule->update($request->validated());

        return response()->json([
            'message' => 'Availability rule updated successfully.',
            'data' => [
                'availability_rule' => $availabilityRule->refresh(),
            ],
        ]);
    }

    public function destroy(
        AvailabilityRule $availabilityRule
    ): Response {
        Gate::authorize('delete', $availabilityRule);

        $availabilityRule->delete();

        return response()->noContent();
    }
}
