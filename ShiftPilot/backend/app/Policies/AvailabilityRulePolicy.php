<?php

namespace App\Policies;

use App\Models\AvailabilityRule;
use App\Models\CompanyMembership;
use App\Models\Feature;
use App\Models\User;

class AvailabilityRulePolicy
{
    public function viewForMembership(
        User $user,
        CompanyMembership $companyMembership
    ): bool {
        if (! $companyMembership->isActive()
            || ! $this->featureIsEnabled($companyMembership)) {
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
        AvailabilityRule $availabilityRule
    ): bool {
        $companyMembership =
            $availabilityRule->companyMembership;

        if (! $this->featureIsEnabled($companyMembership)) {
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

    public function createForMembership(
        User $user,
        CompanyMembership $companyMembership
    ): bool {
        if (! $companyMembership->isActive()
            || ! $this->featureIsEnabled($companyMembership)) {
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
        AvailabilityRule $availabilityRule
    ): bool {
        $companyMembership = $availabilityRule->companyMembership;

        if (! $companyMembership->isActive()
    || ! $this->featureIsEnabled($companyMembership)) {
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
        AvailabilityRule $availabilityRule
    ): bool {
        return $this->update($user, $availabilityRule);
    }

    private function featureIsEnabled(
        CompanyMembership $companyMembership
    ): bool {
        return $companyMembership
            ->company
            ->hasFeature(Feature::KEY_RECURRING_AVAILABILITY);
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
