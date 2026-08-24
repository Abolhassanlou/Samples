<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CrewflowApiClient;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    use ApiResponse;

    public function assign(Request $request, string $company, CrewflowApiClient $client)
    {
        $data = $request->validate([
            'plan_id' => ['required', 'integer'],
            'status' => ['sometimes', 'in:trial,active'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $result = $client->assignSubscription($company, $data);

        return $this->success($result, 'Plan assigned', 201);
    }

    public function extend(Request $request, int $subscription, CrewflowApiClient $client)
    {
        $data = $request->validate([
            'expires_at' => ['required', 'date', 'after:today'],
        ]);

        $result = $client->extendSubscription($subscription, $data);

        return $this->success($result, 'Subscription extended');
    }

    public function expire(int $subscription, CrewflowApiClient $client)
    {
        $result = $client->expireSubscription($subscription);

        return $this->success($result, 'Subscription expired');
    }
}
