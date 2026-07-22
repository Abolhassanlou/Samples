<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Region;
use App\Models\User;

class RegionPolicy
{
    public function viewAnyForCompany(
        User $user,
        Company $company
    ): bool {
        return $this->activeMembership(
            $user,
            $company
        ) !== null;
    }

    public function createForCompany(
        User $user,
        Company $company
    ): bool {
        return $this->isCompanyAdmin($user, $company);
    }

    public function view(
        User $user,
        Region $region
    ): bool {
        return $this->activeMembership(
            $user,
            $region->company
        ) !== null;
    }

    public function update(
        User $user,
        Region $region
    ): bool {
        return $this->isCompanyAdmin(
            $user,
            $region->company
        );
    }

    public function delete(
        User $user,
        Region $region
    ): bool {
        return $this->update($user, $region);
    }

    public function manageAssignments(
        User $user,
        Region $region
    ): bool {
        return $this->update($user, $region);
    }

    private function isCompanyAdmin(
        User $user,
        Company $company
    ): bool {
        return $this->activeMembership(
            $user,
            $company
        )?->role ===
            CompanyMembership::ROLE_COMPANY_ADMIN;
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
