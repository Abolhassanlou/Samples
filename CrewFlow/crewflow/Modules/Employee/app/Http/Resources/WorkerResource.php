<?php

namespace Modules\Employee\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'date_of_birth' => $this->date_of_birth,
            'gender' => $this->gender,
            'marital_status' => $this->marital_status,
            'nationality' => $this->nationality,
            'native_language' => $this->native_language,
            'social_security_number' => $this->social_security_number,
            'german_language_level' => $this->german_language_level,
            'languages_spoken' => $this->languages_spoken,
            'street' => $this->street,
            'house_number' => $this->house_number,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'country' => $this->country,
            'residence_type' => $this->residence_type,
            'bank_name' => $this->bank_name,
            'bank_account_holder_name' => $this->bank_account_holder_name,
            'iban' => $this->iban,
            'bic' => $this->bic,
            'status' => $this->status,
            'work_authorization_status' => $this->work_authorization_status,
            'work_authorization_type' => $this->work_authorization_type,
            'work_authorization_expiry_date' => $this->work_authorization_expiry_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
