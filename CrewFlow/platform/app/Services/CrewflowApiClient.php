<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * The ONLY way this project ever writes to crewflow's data. Every
 * mutating action (suspend a company, assign/extend/expire a
 * subscription, create a plan) goes through here — never through direct
 * writes on the 'central' database connection — so crewflow's own
 * validation, events, and stancl/tenancy logic are never bypassed.
 *
 * Authenticates as a SERVICE (this whole Platform project), not as the
 * individual PlatformUser making the request — see crewflow's
 * AuthenticatePlatformService middleware docblock for the matching side
 * of this. Which human is allowed to trigger which action is decided
 * entirely in THIS project (via spatie/laravel-permission), before this
 * client is ever called.
 */
class CrewflowApiClient
{
    private function client(): PendingRequest
    {
        return Http::withToken(config('services.crewflow.api_key'))
            ->baseUrl(rtrim(config('services.crewflow.base_url'), '/').'/api/internal')
            ->acceptJson();
    }

    public function listCompanies(): array
    {
        return $this->client()->get('companies')->throw()->json('data');
    }

    public function getCompany(string $companyId): array
    {
        return $this->client()->get("companies/{$companyId}")->throw()->json('data');
    }

    public function suspendCompany(string $companyId): array
    {
        return $this->client()->post("companies/{$companyId}/suspend")->throw()->json();
    }

    public function unsuspendCompany(string $companyId): array
    {
        return $this->client()->post("companies/{$companyId}/unsuspend")->throw()->json();
    }

    public function listPlans(): array
    {
        return $this->client()->get('plans')->throw()->json('data');
    }

    public function createPlan(array $data): array
    {
        return $this->client()->post('plans', $data)->throw()->json();
    }

    public function assignSubscription(string $companyId, array $data): array
    {
        return $this->client()->post("companies/{$companyId}/subscription", $data)->throw()->json();
    }

    public function extendSubscription(int $subscriptionId, array $data): array
    {
        return $this->client()->post("subscriptions/{$subscriptionId}/extend", $data)->throw()->json();
    }

    public function expireSubscription(int $subscriptionId): array
    {
        return $this->client()->post("subscriptions/{$subscriptionId}/expire")->throw()->json();
    }
}
