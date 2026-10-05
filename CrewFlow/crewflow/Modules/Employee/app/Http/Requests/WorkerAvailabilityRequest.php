<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkerAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // `present`, not `required`/`min:1` — sync() is a full
            // replace, so "I'm not available at all right now" is a
            // legitimate save (an empty week), not a validation error.
            //
            // end_time is H:i, which has no 24:00 — a slot running to
            // midnight is stored as 23:59 (worker-portal's weekly grid
            // does this for its last hour). A slot can't span midnight
            // itself (end must be after start within the same day); an
            // overnight stretch is two slots, one on each day.
            'slots' => ['present', 'array'],
            'slots.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'slots.*.start_time' => ['required', 'date_format:H:i'],
            'slots.*.end_time' => ['required', 'date_format:H:i', 'after:slots.*.start_time'],
        ];
    }
}
