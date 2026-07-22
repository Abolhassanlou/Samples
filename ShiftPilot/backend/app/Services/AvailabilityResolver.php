<?php

namespace App\Services;

use App\Models\AvailabilityOverride;
use App\Models\AvailabilityRule;
use App\Models\CompanyMembership;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class AvailabilityResolver
{
    public const STATUS_AVAILABLE = 'available';

    public const STATUS_PREFERRED = 'preferred';

    public const STATUS_PARTIALLY_AVAILABLE = 'partially_available';

    public const STATUS_UNAVAILABLE = 'unavailable';

    public const STATUS_UNKNOWN = 'unknown';

    public const STATUS_TIME_CONFLICT = 'time_conflict';

    public function resolve(
        CompanyMembership $membership,
        string $date,
        string $startTime,
        string $endTime
    ): string {
        $resolvedDate = CarbonImmutable::parse($date);
        $resolvedStartTime = $this->normalizeTime($startTime);
        $resolvedEndTime = $this->normalizeTime($endTime);

        if ($resolvedEndTime <= $resolvedStartTime) {
            throw new InvalidArgumentException(
                'The end time must be later than the start time.'
            );
        }

        $overrides = $membership
            ->availabilityOverrides()
            ->forDate($resolvedDate)
            ->get();

        if ($overrides->isNotEmpty()) {
            return $this->resolveEntries(
                $overrides,
                $resolvedStartTime,
                $resolvedEndTime
            );
        }

        $weeklyRules = $membership
            ->availabilityRules()
            ->active()
            ->effectiveOn($resolvedDate)
            ->where('weekday', $resolvedDate->isoWeekday())
            ->get();

        if ($weeklyRules->isEmpty()) {
            return self::STATUS_UNKNOWN;
        }

        return $this->resolveEntries(
            $weeklyRules,
            $resolvedStartTime,
            $resolvedEndTime
        );
    }

    /**
     * @param Collection<int, AvailabilityRule|AvailabilityOverride> $entries
     */
    private function resolveEntries(
        Collection $entries,
        string $requestedStart,
        string $requestedEnd
    ): string {
        $wholeDayEntry = $entries->first(
            fn (AvailabilityRule|AvailabilityOverride $entry): bool =>
                $entry->start_time === null
                && $entry->end_time === null
        );

        if ($wholeDayEntry !== null) {
            return $this->normalizeStatus($wholeDayEntry->status);
        }

        foreach ([
            AvailabilityRule::STATUS_UNAVAILABLE,
            AvailabilityRule::STATUS_PREFERRED,
            AvailabilityRule::STATUS_AVAILABLE,
        ] as $status) {
            $fullyCoveringEntry = $entries->first(
                fn (AvailabilityRule|AvailabilityOverride $entry): bool =>
                    $entry->status === $status
                    && $entry->start_time <= $requestedStart
                    && $entry->end_time >= $requestedEnd
            );

            if ($fullyCoveringEntry !== null) {
                return $this->normalizeStatus(
                    $fullyCoveringEntry->status
                );
            }
        }

        $hasPartialOverlap = $entries->contains(
            fn (AvailabilityRule|AvailabilityOverride $entry): bool =>
                $entry->start_time < $requestedEnd
                && $entry->end_time > $requestedStart
        );

        if ($hasPartialOverlap) {
            return self::STATUS_PARTIALLY_AVAILABLE;
        }

        return self::STATUS_UNAVAILABLE;
    }

    private function normalizeStatus(string $status): string
    {
        return match ($status) {
            AvailabilityRule::STATUS_AVAILABLE =>
                self::STATUS_AVAILABLE,

            AvailabilityRule::STATUS_PREFERRED =>
                self::STATUS_PREFERRED,

            AvailabilityRule::STATUS_UNAVAILABLE =>
                self::STATUS_UNAVAILABLE,

            default => self::STATUS_UNKNOWN,
        };
    }

    private function normalizeTime(string $time): string
    {
        return CarbonImmutable::parse($time)->format('H:i:s');
    }
}