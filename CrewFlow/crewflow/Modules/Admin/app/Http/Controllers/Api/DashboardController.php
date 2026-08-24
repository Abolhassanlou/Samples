<?php

namespace Modules\Admin\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Modules\Authentication\Models\User;
use Modules\Core\Traits\ApiResponse;
use Modules\Shift\Models\Shift;
use Modules\Transaction\Models\Transaction;

/**
 * Aggregates data already owned by other modules — this module doesn't
 * introduce any new "source of truth", it's a read-only summary view.
 */
class DashboardController extends Controller
{
    use ApiResponse;

    public function stats()
    {
        return $this->success([
            'workers_count' => User::role('Worker')->count(),
            'active_shifts_count' => Shift::whereIn('status', ['open', 'partially_filled', 'filled', 'in_progress'])->count(),
            'shifts_this_week_count' => Shift::whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'pending_transactions_total' => (float) Transaction::where('status', 'pending')->sum('amount'),
            'pending_transactions_count' => Transaction::where('status', 'pending')->count(),
        ]);
    }
}
