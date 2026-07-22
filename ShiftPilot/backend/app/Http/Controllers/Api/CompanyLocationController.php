<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyLocation\StoreCompanyLocationRequest;
use App\Http\Requests\CompanyLocation\UpdateCompanyLocationRequest;
use App\Models\Company;
use App\Models\CompanyLocation;
use App\Models\CompanyMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CompanyLocationController extends Controller
{
    public function index(Company $company): JsonResponse
    {
        Gate::authorize(
            'viewAnyForCompany',
            [CompanyLocation::class, $company]
        );

        $membership = auth()->user()
            ->companyMemberships()
            ->where('company_id', $company->id)
            ->where(
                'status',
                CompanyMembership::STATUS_ACTIVE
            )
            ->firstOrFail();

        $query = $company->locations()
            ->orderBy('name');

        if (
            $membership->role
                === CompanyMembership::ROLE_DISPATCHER
            && ! $membership->access_all_locations
        ) {
            $query->whereHas(
                'memberships',
                fn ($query) => $query->whereKey(
                    $membership->id
                )
            );
        }

        if (
            $membership->role
            === CompanyMembership::ROLE_DISPATCHER
        ) {
            $query->where('is_active', true);
        }

        return response()->json([
            'data' => [
                'company_locations' => $query->get(),
            ],
        ]);
    }

    public function store(
        StoreCompanyLocationRequest $request,
        Company $company
    ): JsonResponse {
        Gate::authorize(
            'createForCompany',
            [CompanyLocation::class, $company]
        );

        $companyLocation = $company
            ->locations()
            ->create($request->validated());

        return response()->json([
            'message' => 'Company location created successfully.',
            'data' => [
                'company_location' => $companyLocation,
            ],
        ], Response::HTTP_CREATED);
    }

    public function show(
        CompanyLocation $companyLocation
    ): JsonResponse {
        Gate::authorize('view', $companyLocation);

        return response()->json([
            'data' => [
                'company_location' => $companyLocation,
            ],
        ]);
    }

    public function update(
        UpdateCompanyLocationRequest $request,
        CompanyLocation $companyLocation
    ): JsonResponse {
        Gate::authorize('update', $companyLocation);

        $companyLocation->update($request->validated());

        return response()->json([
            'message' => 'Company location updated successfully.',
            'data' => [
                'company_location' => $companyLocation->refresh(),
            ],
        ]);
    }

    public function destroy(
        CompanyLocation $companyLocation
    ): Response {
        Gate::authorize('delete', $companyLocation);

        $companyLocation->delete();

        return response()->noContent();
    }
}
