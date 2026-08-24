<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Central\Plan;
use App\Services\CrewflowApiClient;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->success(Plan::with('limits')->get());
    }

    public function store(Request $request, CrewflowApiClient $client)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_cycle' => ['sometimes', 'in:monthly,yearly'],
            'limits' => ['sometimes', 'array'],
            'limits.*.limit_type' => ['required_with:limits', 'in:max_workers,max_dispatchers,max_admins,max_branches'],
            'limits.*.max_value' => ['nullable', 'integer', 'min:0'],
            'limits.*.enforcement_mode' => ['required_with:limits', 'in:hard_block,soft_warning'],
        ]);

        $result = $client->createPlan($data);

        return $this->success($result, 'Plan created', 201);
    }
}
