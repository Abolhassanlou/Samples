<?php

namespace App\Services;

use App\Models\CompanyMembership;
use App\Models\Qualification;
use App\Models\Region;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class EmployeeEligibilityMatcher
{
    public const MATCH_ALL = 'match_all';

    public const MATCH_ANY = 'match_any';

    public const MATCH_MODES = [
        self::MATCH_ALL,
        self::MATCH_ANY,
    ];

    /**
     * @param  iterable<Qualification>  $qualifications
     */
    public function matchesQualifications(
        CompanyMembership $membership,
        iterable $qualifications,
        CarbonInterface|string $date,
        string $matchMode = self::MATCH_ALL
    ): bool {
        if (! in_array(
            $matchMode,
            self::MATCH_MODES,
            true
        )) {
            throw new InvalidArgumentException(
                'Unsupported qualification match mode.'
            );
        }

        $qualificationCollection =
            collect($qualifications);

        if ($qualificationCollection->isEmpty()) {
            return true;
        }

        $matches = fn (
            Qualification $qualification
        ): bool => $membership->hasUsableQualification(
            $qualification,
            $date
        );

        if ($matchMode === self::MATCH_ANY) {
            return $qualificationCollection
                ->contains($matches);
        }

        return $qualificationCollection
            ->every($matches);
    }

    /**
     * @param  Collection<int, CompanyMembership>  $memberships
     * @param  iterable<Qualification>  $qualifications
     * @return Collection<int, CompanyMembership>
     */
    public function filterEligibleEmployees(
        Collection $memberships,
        Region $region,
        iterable $qualifications,
        CarbonInterface|string $date,
        string $matchMode = self::MATCH_ALL
    ): Collection {
        $qualificationCollection =
            collect($qualifications);

        return $memberships
            ->filter(
                function (
                    CompanyMembership $membership
                ) use (
                    $region,
                    $qualificationCollection,
                    $date,
                    $matchMode
                ): bool {
                    if (
                        $membership->role
                        !== CompanyMembership::ROLE_EMPLOYEE
                    ) {
                        return false;
                    }

                    if (! $membership->canWorkInRegion($region)) {
                        return false;
                    }

                    return $this->matchesQualifications(
                        $membership,
                        $qualificationCollection,
                        $date,
                        $matchMode
                    );
                }
            )
            ->values();
    }
}
