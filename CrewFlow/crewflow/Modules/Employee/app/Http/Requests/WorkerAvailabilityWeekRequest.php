<?php

namespace Modules\Employee\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Employee\Services\AvailabilityRepeat;

/**
 * One week's availability pattern plus how far to repeat it — see
 * WorkerAvailabilityController::syncWeeks(). `slots` is the pattern for
 * the week starting `week_start` (day_of_week 0 = Sunday … 6 = Saturday,
 * same as everywhere else); it may be empty, which means "not available
 * at all" for the whole span — also how a worker books time off.
 */
class WorkerAvailabilityWeekRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'week_start' => ['required', 'date_format:Y-m-d'],

            'slots' => ['present', 'array'],
            'slots.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'slots.*.start_time' => ['required', 'date_format:H:i'],
            'slots.*.end_time' => ['required', 'date_format:H:i', 'after:slots.*.start_time'],

            'repeat' => ['required', 'array'],
            'repeat.mode' => ['required', 'in:none,weeks,months,until'],
            'repeat.count' => ['required_if:repeat.mode,weeks,months', 'nullable', 'integer', 'min:1', 'max:52'],
            'repeat.until' => ['required_if:repeat.mode,until', 'nullable', 'date_format:Y-m-d'],
        ];
    }

    /**
     * Cross-field rules, checked only once the basic shape above passes
     * (so a malformed date never reaches Carbon here).
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $weekStart = Carbon::createFromFormat('Y-m-d', $this->input('week_start'))->startOfDay();

            if (! $weekStart->isMonday()) {
                $validator->errors()->add('week_start', 'The week must start on a Monday.');

                return;
            }

            if ($weekStart->lt(now()->startOfWeek(Carbon::MONDAY)->startOfDay())) {
                $validator->errors()->add('week_start', "Availability can't be set for a week that has already passed.");

                return;
            }

            $repeat = $this->input('repeat');

            if (($repeat['mode'] ?? null) === 'months' && (int) $repeat['count'] > 12) {
                $validator->errors()->add('repeat.count', 'A repeat is limited to 12 months.');

                return;
            }

            $end = AvailabilityRepeat::endDate($weekStart, $repeat);

            if ($end->lt($weekStart)) {
                $validator->errors()->add('repeat.until', 'The end date must be on or after the start of the week.');
            } elseif ($end->gt($weekStart->copy()->addDays(AvailabilityRepeat::MAX_SPAN_DAYS - 1))) {
                $validator->errors()->add('repeat.until', 'Availability can be set at most one year ahead in one go.');
            }
        });
    }
}
