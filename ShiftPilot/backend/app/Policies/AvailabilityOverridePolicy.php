<?php

namespace App\Policies;

use App\Models\AvailabilityOverride;
use App\Models\CompanyMembership;
use App\Models\User;

class AvailabilityOverridePolicy
{
    public function viewForMembership(
        User $user,
        CompanyMembership $companyMembership
    ): bool {
        if (! $companyMembership->isActive()) {
            return false;
        }

        return $this->isOwner($user, $companyMembership)
            || $this->hasCompanyRole(
                $user,
                $companyMembership,
                [
                    CompanyMembership::ROLE_COMPANY_ADMIN,
                    CompanyMembership::ROLE_DISPATCHER,
                ]
            );
    }

    public function view(
        User $user,
        AvailabilityOverride $availabilityOverride
    ): bool {
        return $this->isOwner(
            $user,
            $availabilityOverride->companyMembership
        ) || $this->hasCompanyRole(
            $user,
            $availabilityOverride->companyMembership,
            [
                CompanyMembership::ROLE_COMPANY_ADMIN,
                CompanyMembership::ROLE_DISPATCHER,
            ]
        );
    }

    public function createForMembership(
        User $user,
        CompanyMembership $companyMembership
    ): bool {
        if (! $companyMembership->isActive()) {
            return false;
        }

        return $this->isOwner($user, $companyMembership)
            || $this->hasCompanyRole(
                $user,
                $companyMembership,
                [CompanyMembership::ROLE_COMPANY_ADMIN]
            );
    }

    public function update(
        User $user,
        AvailabilityOverride $availabilityOverride
    ): bool {
        $companyMembership =
            $availabilityOverride->companyMembership;

        if (! $companyMembership->isActive()) {
            return false;
        }

        return $this->isOwner($user, $companyMembership)
            || $this->hasCompanyRole(
                $user,
                $companyMembership,
                [CompanyMembership::ROLE_COMPANY_ADMIN]
            );
    }

    public function delete(
        User $user,
        AvailabilityOverride $availabilityOverride
    ): bool {
        return $this->update($user, $availabilityOverride);
    }

    private function isOwner(
        User $user,
        CompanyMembership $companyMembership
    ): bool {
        return $companyMembership->user_id === $user->id;
    }

    /**
     * @param  array<int, string>  $roles
     */
    private function hasCompanyRole(
        User $user,
        CompanyMembership $companyMembership,
        array $roles
    ): bool {
        return $user->companyMemberships()
            ->where('company_id', $companyMembership->company_id)
            ->where('status', CompanyMembership::STATUS_ACTIVE)
            ->whereIn('role', $roles)
            ->exists();
    }
}
