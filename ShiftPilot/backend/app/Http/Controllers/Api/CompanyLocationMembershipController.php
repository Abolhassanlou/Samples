<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyLocation;
use App\Models\CompanyMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CompanyLocationMembershipController extends Controller
{
    public function store(
        CompanyLocation $companyLocation,
        CompanyMembership $companyMembership
    ): JsonResponse {
        Gate::authorize(
            'manageMemberships',
            $companyLocation
        );

        $this->ensureCompatible(
            $companyLocation,
            $companyMembership
        );

        $companyLocation
            ->memberships()
            ->syncWithoutDetaching([
                $companyMembership->id,
            ]);

        return response()->json([
            'message' => 'Member assigned to company location successfully.',
            'data' => [
                'company_location_id' => $companyLocation->id,
                'company_membership_id' => $companyMembership->id,
            ],
        ]);
    }

    public function destroy(
        CompanyLocation $companyLocation,
        CompanyMembership $companyMembership
    ): Response {
        Gate::authorize(
            'manageMemberships',
            $companyLocation
        );

        $this->ensureCompatible(
            $companyLocation,
            $companyMembership
        );

        $companyLocation
            ->memberships()
            ->detach($companyMembership->id);

        return response()->noContent();
    }

    private function ensureCompatible(
        CompanyLocation $companyLocation,
        CompanyMembership $companyMembership
    ): void {
        if (
            $companyLocation->company_id
                !== $companyMembership->company_id
        ) {
            throw ValidationException::withMessages([
                'company_membership_id' => [
                    'The member and location must belong to the same company.',
                ],
            ]);
        }

        if (! $companyMembership->isActive()) {
            throw ValidationException::withMessages([
                'company_membership_id' => [
                    'Only active company members can be assigned to a location.',
                ],
            ]);
        }
    }
}
