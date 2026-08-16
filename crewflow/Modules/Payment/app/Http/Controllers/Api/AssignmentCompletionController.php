<?php

namespace Modules\Payment\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Traits\ApiResponse;
use Modules\Payment\Http\Resources\WorkLogResource;
use Modules\Payment\Models\CompletionProof;
use Modules\Payment\Models\WorkLog;
use Modules\Setting\Models\CompanySettings;
use Modules\Shift\Models\Assignment;

class AssignmentCompletionController extends Controller
{
    use ApiResponse;

    /**
     * Marks a confirmed Assignment as completed: logs actual hours/pay
     * (WorkLog), records completion proof, and flips the Assignment's own
     * status to "completed". Either the worker themselves or someone with
     * shifts.dispatch may do this.
     */
    public function store(Request $request, Assignment $assignment)
    {
        $isOwnAssignment = $assignment->worker_id === $request->user()->id;
        $canDispatch = $request->user()->can('shifts.dispatch');

        abort_unless($isOwnAssignment || $canDispatch, 403);

        if ($assignment->status !== 'confirmed') {
            return $this->error('Only a confirmed assignment can be marked complete.', 422);
        }

        $data = $request->validate([
            'hours_worked' => ['nullable', 'numeric', 'min:0'],
            'proof_type' => ['required', 'in:uploaded_document,digital_signature'],
            'file' => ['required_if:proof_type,uploaded_document', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
            'signature_data' => ['required_if:proof_type,digital_signature', 'string'],
        ]);

        $shift = $assignment->shift;

        $hoursWorked = $data['hours_worked'] ?? $shift->starts_at->diffInMinutes($shift->ends_at) / 60;
        $baseAmount = $shift->rate_type === 'hourly'
            ? round($hoursWorked * (float) $shift->hourly_rate, 2)
            : (float) $shift->fixed_amount;
        $transportAmount = (float) ($assignment->transport_amount ?? 0);

        $workLog = WorkLog::create([
            'assignment_id' => $assignment->id,
            'worker_id' => $assignment->worker_id,
            'shift_id' => $shift->id,
            'hours_worked' => $hoursWorked,
            'base_amount' => $baseAmount,
            'transport_amount' => $transportAmount,
            'total_amount' => $baseAmount + $transportAmount,
            'work_date' => $shift->starts_at->toDateString(),
        ]);

        $filePath = null;
        if ($data['proof_type'] === 'uploaded_document') {
            $filePath = $request->file('file')->store('completion-proofs', 'local');
        }

        CompletionProof::create([
            'assignment_id' => $assignment->id,
            'proof_type' => $data['proof_type'],
            'file_path' => $filePath,
            'signature_data' => $data['signature_data'] ?? null,
        ]);

        $assignment->update(['status' => 'completed']);

        return $this->success([
            'work_log' => new WorkLogResource($workLog),
            'warnings' => $this->overtimeWarnings($assignment->worker_id),
        ], 'Assignment marked complete', 201);
    }

    /**
     * Non-blocking overtime/overpay warning, per the project's rule:
     * flag it, never prevent the completion. Rolling 7-day window.
     */
    private function overtimeWarnings(int $workerId): array
    {
        $since = now()->subDays(7)->toDateString();

        $weeklyHours = WorkLog::where('worker_id', $workerId)->where('work_date', '>=', $since)->sum('hours_worked');
        $weeklyIncome = WorkLog::where('worker_id', $workerId)->where('work_date', '>=', $since)->sum('total_amount');

        $settings = CompanySettings::current();

        return [
            'weekly_hours' => (float) $weeklyHours,
            'weekly_income' => (float) $weeklyIncome,
            'hours_exceeded' => $settings->warning_hour_threshold !== null && $weeklyHours > $settings->warning_hour_threshold,
            'income_exceeded' => $settings->warning_income_threshold !== null && $weeklyIncome > $settings->warning_income_threshold,
        ];
    }
}
