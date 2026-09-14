<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:female,male,diverse'],
            'marital_status' => ['nullable', 'in:single,married,separated,widowed,registered_partnership'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'native_language' => ['nullable', 'string', 'max:255'],
            // A value constraint (exactly 10 digits when provided), NOT
            // a presence requirement — this same endpoint accepts partial
            // payloads from three different frontend forms (Personal
            // details / Address / Bank details), so nothing here can be
            // "required" without breaking the other two, which never
            // send this field at all. "Required" is enforced purely on
            // the frontend, on the one form that actually collects it.
            'social_security_number' => ['nullable', 'regex:/^[0-9]{10}$/'],
            'german_language_level' => ['nullable', 'in:none,basic,conversational,fluent,native'],
            'languages_spoken' => ['nullable', 'array'],
            'languages_spoken.*' => ['string', 'max:100'],

            'street' => ['nullable', 'string', 'max:255'],
            'house_number' => ['nullable', 'string', 'max:20'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'residence_type' => ['nullable', 'in:main,secondary'],

            'bank_name' => ['nullable', 'string', 'max:255'],
            // The account-holder-name-matches-the-worker check itself
            // lives in WorkerController::update() (needs the worker's
            // own first/last name to compare against, which this Request
            // alone can't see cleanly) — see that method's docblock.
            'bank_account_holder_name' => ['nullable', 'string', 'max:255'],
            'iban' => ['nullable', 'string', 'max:34'],
            'bic' => ['nullable', 'string', 'max:11'],

            'status' => ['sometimes', 'in:pending,active,inactive,blocked'],
            'work_authorization_status' => ['sometimes', 'in:pending,valid,expired,not_required,rejected'],
            'work_authorization_type' => ['nullable', 'string', 'max:255'],
            'work_authorization_expiry_date' => ['nullable', 'date'],
        ];
    }
}
