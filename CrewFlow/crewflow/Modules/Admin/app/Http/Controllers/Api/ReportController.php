<?php

namespace Modules\Admin\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Payment\Models\WorkLog;
use Modules\Shift\Models\Shift;

class ReportController extends Controller
{
    use ApiResponse;

    /**
     * Total hours/pay per worker over a date range — from Payment's own
     * WorkLog, just grouped and summarized here.
     */
    public function workerHours(Request $request)
    {
        $data = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $rows = WorkLog::whereBetween('work_date', [$data['from'], $data['to']])
            ->with('worker')
            ->get()
            ->groupBy('worker_id')
            ->map(fn ($logs) => [
                'worker_id' => $logs->first()->worker_id,
                'worker_name' => $logs->first()->worker->name,
                'total_hours' => (float) $logs->sum('hours_worked'),
                'total_pay' => (float) $logs->sum('total_amount'),
                'shifts_completed' => $logs->count(),
            ])
            ->values();

        return $this->success($rows);
    }

    /**
     * Shift counts by status over a date range — from Shift's own table,
     * just grouped and summarized here.
     */
    public function shiftSummary(Request $request)
    {
        $data = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $counts = Shift::whereBetween('starts_at', [$data['from'], $data['to']])
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return $this->success([
            'from' => $data['from'],
            'to' => $data['to'],
            'by_status' => $counts,
            'total' => $counts->sum(),
        ]);
    }
}
