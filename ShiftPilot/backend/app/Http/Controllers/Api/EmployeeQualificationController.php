<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Eligibility\StoreEmployeeQualificationRequest;
use App\Models\CompanyMembership;
use App\Models\EmployeeQualification;
use App\Models\Qualification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class EmployeeQualificationController extends Controller
{
    public function store(
        StoreEmployeeQualificationRequest $request,
        CompanyMembership $companyMembership,
        Qualification $qualification
    ): JsonResponse {
        Gate::authorize(
            'manageAssignments',
            $qualification
        );

        $this->ensureCompatible(
            $companyMembership,
            $qualification
        );

        $data = $request->validated();

        $status = $data['status']
            ?? EmployeeQualification::STATUS_VERIFIED;

        $isVerified = $status ===
            EmployeeQualification::STATUS_VERIFIED;

        $employeeQualification =
            EmployeeQualification::query()
                ->updateOrCreate(
                    [
                        'company_membership_id' => $companyMembership->id,
                        'qualification_id' => $qualification->id,
                    ],
                    [
                        'level' => $data['level'] ?? null,
                        'status' => $status,
                        'issued_at' => $data['issued_at'] ?? null,
                        'expires_at' => $data['expires_at'] ?? null,
                        'verified_by_user_id' => $isVerified
                                ? $request->user()->id
                                : null,
                        'verified_at' => $isVerified ? now() : null,
                        'notes' => $data['notes'] ?? null,
                    ]
                );

        return response()->json([
            'message' => 'Employee qualification saved successfully.',
            'data' => [
                'employee_qualification' => $employeeQualification,
            ],
        ]);
    }

    public function destroy(
        CompanyMembership $companyMembership,
        Qualification $qualification
    ): Response {
        Gate::authorize(
            'manageAssignments',
            $qualification
        );

        $this->ensureCompatible(
            $companyMembership,
            $qualification
        );

        EmployeeQualification::query()
            ->where(
                'company_membership_id',
                $companyMembership->id
            )
            ->where(
                'qualification_id',
                $qualification->id
            )
            ->delete();

        return response()->noContent();
    }

    private function ensureCompatible(
        CompanyMembership $companyMembership,
        Qualification $qualification
    ): void {
        if (
            $companyMembership->company_id
            !== $qualification->company_id
        ) {
            throw ValidationException::withMessages([
                'company_membership_id' => [
                    'The member and qualification must belong to the same company.',
                ],
            ]);
        }

        if (! $companyMembership->isActive()) {
            throw ValidationException::withMessages([
                'company_membership_id' => [
                    'Only active company members can receive qualifications.',
                ],
            ]);
        }
    }
}
