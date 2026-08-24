<?php

namespace Modules\Transaction\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Transaction\Http\Requests\RecurringBillingProfileRequest;
use Modules\Transaction\Http\Resources\RecurringBillingProfileResource;
use Modules\Transaction\Models\RecurringBillingProfile;

/**
 * Authorization for every action here is handled at the route level
 * (permission:clients.manage), same as the rest of this module.
 */
class RecurringBillingProfileController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->success(RecurringBillingProfileResource::collection(RecurringBillingProfile::all()));
    }

    public function store(RecurringBillingProfileRequest $request)
    {
        $profile = RecurringBillingProfile::create([
            ...$request->validated(),
            'is_active' => $request->validated('is_active', true),
        ]);

        return $this->success(new RecurringBillingProfileResource($profile), 'Recurring billing profile created', 201);
    }

    public function update(RecurringBillingProfileRequest $request, RecurringBillingProfile $recurringBillingProfile)
    {
        $recurringBillingProfile->update($request->validated());

        return $this->success(new RecurringBillingProfileResource($recurringBillingProfile), 'Recurring billing profile updated');
    }

    public function destroy(RecurringBillingProfile $recurringBillingProfile)
    {
        $recurringBillingProfile->delete();

        return $this->success(null, 'Recurring billing profile deleted');
    }
}
