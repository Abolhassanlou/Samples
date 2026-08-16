<?php

namespace Modules\Transaction\Observers;

use Modules\Payment\Models\WorkLog;
use Modules\Transaction\Models\Transaction;

/**
 * Mirrors the Notification module's pattern exactly: Payment has no idea
 * this module exists. When a WorkLog is created (a shift was completed),
 * if that shift has a Client, auto-create a pending Transaction billing
 * that client — amount based on the Shift's client_billing_rate, not the
 * worker's own pay rate.
 */
class WorkLogObserver
{
    public function created(WorkLog $workLog): void
    {
        $shift = $workLog->shift;

        if (! $shift->client_id || ! $shift->client_billing_rate) {
            return;
        }

        Transaction::create([
            'client_id' => $shift->client_id,
            'shift_id' => $shift->id,
            'amount' => round((float) $shift->client_billing_rate * (float) $workLog->hours_worked, 2),
            'status' => 'pending',
            'description' => "Billing for shift \"{$shift->title}\" completed on {$workLog->work_date}.",
            'created_by' => $shift->created_by,
        ]);
    }
}
