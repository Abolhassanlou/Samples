<?php

namespace Modules\Tenancy\Http\Controllers\Api\Admin;

use Illuminate\Routing\Controller;
use Modules\Tenancy\Models\Company;

/**
 * Authorization for every action here is handled entirely at the route
 * level via the AuthenticatePlatformService middleware (routes/api.php)
 * — a static service API key, not a per-user permission. Fine-grained
 * human-level authorization (which platform admin can do what) now
 * happens inside the separate Platform project, before it ever calls
 * this API. crewflow only verifies "is this a legitimate call from the
 * Platform service", not "which human is asking".
 */
class CompanyManagementController extends Controller
{
    public function index()
    {
        $companies = Company::with(['activeSubscription.plan', 'domains'])->get()->map(
            fn (Company $company) => $this->transform($company)
        );

        return response()->json(['success' => true, 'data' => $companies]);
    }

    public function show(Company $company)
    {
        $company->load(['activeSubscription.plan', 'subscriptions.plan', 'domains']);

        return response()->json(['success' => true, 'data' => $this->transform($company, detailed: true)]);
    }

    /**
     * Suspending does NOT touch the subscription — a company can have an
     * active, fully paid subscription and still be manually suspended
     * (e.g. for abuse), which is a separate concern from billing status.
     */
    public function suspend(Company $company)
    {
        $company->update(['is_suspended' => true]);

        return response()->json(['success' => true, 'message' => 'Company suspended']);
    }

    public function unsuspend(Company $company)
    {
        $company->update(['is_suspended' => false]);

        return response()->json(['success' => true, 'message' => 'Company reactivated']);
    }

    private function transform(Company $company, bool $detailed = false): array
    {
        $data = [
            'id' => $company->id,
            'company_code' => $company->company_code,
            'name' => $company->name,
            'is_suspended' => $company->is_suspended,
            'domain' => $company->domains->first()?->domain,
            'subscription' => $company->activeSubscription ? [
                'plan' => $company->activeSubscription->plan?->name,
                'status' => $company->activeSubscription->status,
                'expires_at' => $company->activeSubscription->expires_at,
            ] : null,
            'created_at' => $company->created_at,
        ];

        if ($detailed) {
            $data['all_subscriptions'] = $company->subscriptions->map(fn ($s) => [
                'id' => $s->id,
                'plan' => $s->plan?->name,
                'status' => $s->status,
                'started_at' => $s->started_at,
                'expires_at' => $s->expires_at,
            ]);
        }

        return $data;
    }
}
