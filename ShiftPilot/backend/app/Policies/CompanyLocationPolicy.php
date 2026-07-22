<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\CompanyLocation;
use App\Models\CompanyMembership;
use App\Models\User;

class CompanyLocationPolicy
{
    public function viewAnyForCompany(
        User $user,
        Company $company
    ): bool {
        $membership = $this->activeMembership(
            $user,
            $company
        );

        if (! $membership) {
            return false;
        }

        return in_array(
            $membership->role,
            [
                CompanyMembership::ROLE_COMPANY_ADMIN,
                CompanyMembership::ROLE_DISPATCHER,
            ],
            true
        );
    }

    public function createForCompany(
        User $user,
        Company $company
    ): bool {
        return $this->isCompanyAdmin($user, $company);
    }

    public function view(
        User $user,
        CompanyLocation $companyLocation
    ): bool {
        $membership = $this->activeMembership(
            $user,
            $companyLocation->company
        );

        if (! $membership) {
            return false;
        }

        if (
            $membership->role
            === CompanyMembership::ROLE_COMPANY_ADMIN
        ) {
            return true;
        }

        return $membership->canAccessLocation(
            $companyLocation
        );
    }

    public function update(
        User $user,
        CompanyLocation $companyLocation
    ): bool {
        return $this->isCompanyAdmin(
            $user,
            $companyLocation->company
        );
    }

    public function delete(
        User $user,
        CompanyLocation $companyLocation
    ): bool {
        return $this->update($user, $companyLocation);
    }

    public function manageMemberships(
        User $user,
        CompanyLocation $companyLocation
    ): bool {
        return $this->update($user, $companyLocation);
    }

    private function isCompanyAdmin(
        User $user,
        Company $company
    ): bool {
        $membership = $this->activeMembership(
            $user,
            $company
        );

        return $membership?->role
            === CompanyMembership::ROLE_COMPANY_ADMIN;
    }

    private function activeMembership(
        User $user,
        Company $company
    ): ?CompanyMembership {
        return $user->companyMemberships()
            ->where('company_id', $company->id)
            ->where(
                'status',
                CompanyMembership::STATUS_ACTIVE
            )
            ->first();
    }
}
