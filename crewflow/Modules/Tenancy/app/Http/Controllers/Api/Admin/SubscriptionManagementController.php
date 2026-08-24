<?php

namespace Modules\Tenancy\Http\Controllers\Api\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tenancy\Models\Company;
use Modules\Tenancy\Models\Subscription;

/**
 * Authorization for every action here is handled entirely at the route
 * level via the AuthenticatePlatformService middleware (a static service
 * API key — see routes/api.php and CompanyManagementController's
 * docblock for why).
 */
class SubscriptionManagementController extends Controller
{
    /**
     * Assign a plan to a company — e.g. starting a demo/trial, or moving
     * them from trial to a paid plan. Always creates a NEW subscription
     * row rather than editing an old one, so history is preserved.
     */
    public function assign(Request $request, Company $company)
    {
        $data = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'status' => ['sometimes', 'in:trial,active'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $subscription = Subscription::create([
            'tenant_id' => $company->id,
            'plan_id' => $data['plan_id'],
            'status' => $data['status'] ?? 'trial',
            'started_at' => now(),
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return response()->json(['success' => true, 'message' => 'Plan assigned', 'data' => $subscription], 201);
    }

    /**
     * Manually push a subscription's expiry date out (e.g. "give them
     * another 30 days" or a specific new date), and make sure it's active.
     */
    public function extend(Request $request, Subscription $subscription)
    {
        $data = $request->validate([
            'expires_at' => ['required', 'date', 'after:today'],
        ]);

        $subscription->update([
            'expires_at' => $data['expires_at'],
            'status' => 'active',
        ]);

        return response()->json(['success' => true, 'message' => 'Subscription extended', 'data' => $subscription]);
    }

    /**
     * Manually cut a subscription off right now, regardless of its
     * previous expires_at (e.g. non-payment, requested cancellation).
     */
    public function expire(Subscription $subscription)
    {
        $subscription->update([
            'status' => 'expired',
            'expires_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Subscription expired', 'data' => $subscription]);
    }
}
