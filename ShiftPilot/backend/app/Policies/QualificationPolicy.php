<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Qualification;
use App\Models\User;

class QualificationPolicy
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
        Qualification $qualification
    ): bool {
        return $this->activeMembership(
            $user,
            $qualification->company
        ) !== null;
    }

    public function update(
        User $user,
        Qualification $qualification
    ): bool {
        return $this->isCompanyAdmin(
            $user,
            $qualification->company
        );
    }

    public function delete(
        User $user,
        Qualification $qualification
    ): bool {
        return $this->update($user, $qualification);
    }

    public function manageAssignments(
        User $user,
        Qualification $qualification
    ): bool {
        return $this->update($user, $qualification);
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
