<?php

namespace Modules\Tenancy\Http\Controllers\Api\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tenancy\Models\Plan;

/**
 * `store` authorization is handled entirely at the route level via the
 * AuthenticatePlatformService middleware (a static service API key —
 * see routes/api.php and CompanyManagementController's docblock for why).
 */
class PlanController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => Plan::with('limits')->get()]);
    }

    public function store(Request $request)
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

        $plan = Plan::create([
            'name' => $data['name'],
            'price' => $data['price'],
            'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
        ]);

        foreach ($data['limits'] ?? [] as $limit) {
            $plan->limits()->create($limit);
        }

        return response()->json(['success' => true, 'data' => $plan->load('limits')], 201);
    }
}
