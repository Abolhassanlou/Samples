<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Eligibility\StoreEmployeeRegionRequest;
use App\Models\CompanyMembership;
use App\Models\EmployeeRegion;
use App\Models\Region;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class EmployeeRegionController extends Controller
{
    public function store(
        StoreEmployeeRegionRequest $request,
        CompanyMembership $companyMembership,
        Region $region
    ): JsonResponse {
        Gate::authorize(
            'manageAssignments',
            $region
        );

        $this->ensureCompatible(
            $companyMembership,
            $region
        );

        $data = $request->validated();

        $status = $data['status']
            ?? EmployeeRegion::STATUS_APPROVED;

        $employeeRegion = EmployeeRegion::query()
            ->updateOrCreate(
                [
                    'company_membership_id' => $companyMembership->id,
                    'region_id' => $region->id,
                ],
                [
                    'status' => $status,
                    'approved_by_user_id' => $status ===
                        EmployeeRegion::STATUS_APPROVED
                            ? $request->user()->id
                            : null,
                    'approved_at' => $status ===
                        EmployeeRegion::STATUS_APPROVED
                            ? now()
                            : null,
                    'is_active' => $data['is_active'] ?? true,
                ]
            );

        return response()->json([
            'message' => 'Employee region saved successfully.',
            'data' => [
                'employee_region' => $employeeRegion,
            ],
        ]);
    }

    public function destroy(
        CompanyMembership $companyMembership,
        Region $region
    ): Response {
        Gate::authorize(
            'manageAssignments',
            $region
        );

        $this->ensureCompatible(
            $companyMembership,
            $region
        );

        EmployeeRegion::query()
            ->where(
                'company_membership_id',
                $companyMembership->id
            )
            ->where('region_id', $region->id)
            ->delete();

        return response()->noContent();
    }

    private function ensureCompatible(
        CompanyMembership $companyMembership,
        Region $region
    ): void {
        if (
            $companyMembership->company_id
            !== $region->company_id
        ) {
            throw ValidationException::withMessages([
                'company_membership_id' => [
                    'The member and region must belong to the same company.',
                ],
            ]);
        }

        if (! $companyMembership->isActive()) {
            throw ValidationException::withMessages([
                'company_membership_id' => [
                    'Only active company members can receive regions.',
                ],
            ]);
        }
    }
}
