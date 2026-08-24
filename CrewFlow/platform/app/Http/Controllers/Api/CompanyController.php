<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Central\Company;
use App\Services\CrewflowApiClient;
use App\Traits\ApiResponse;

/**
 * Authorization for every action here is handled entirely at the route
 * level (permission:companies.view / permission:companies.manage —
 * spatie/laravel-permission, fully dynamic, this project's own).
 */
class CompanyController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $companies = Company::with(['activeSubscription.plan', 'domains'])->get()->map(
            fn (Company $company) => $this->transform($company)
        );

        return $this->success($companies);
    }

    public function show(string $company)
    {
        $model = Company::with(['activeSubscription.plan', 'subscriptions.plan', 'domains'])->findOrFail($company);

        return $this->success($this->transform($model, detailed: true));
    }

    public function suspend(string $company, CrewflowApiClient $client)
    {
        $result = $client->suspendCompany($company);

        return $this->success($result, 'Company suspended');
    }

    public function unsuspend(string $company, CrewflowApiClient $client)
    {
        $result = $client->unsuspendCompany($company);

        return $this->success($result, 'Company reactivated');
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
