<?php

namespace Modules\Notification\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Notification\Models\Alarm;
use Modules\Notification\Services\AlarmService;
use Modules\Shift\Models\Assignment;
use Modules\Tenancy\Models\Company;

/**
 * Meant to run frequently (every 5 minutes — see NotificationServiceProvider)
 * via Laravel's scheduler. Since shifts/assignments live inside each
 * company's own database, this walks every tenant and runs the check
 * inside that tenant's context, one at a time.
 *
 * Idempotency: rather than adding "reminder sent" columns to Shift's own
 * Assignment table (which would mean Notification editing Shift's schema —
 * against this project's rule that Shift never even knows Notification
 * exists), we just check whether a matching Alarm row already exists.
 */
class SendShiftReminders extends Command
{
    protected $signature = 'notification:send-shift-reminders';

    protected $description = 'Send 24-hour and 1-hour reminder alarms to workers with confirmed shift assignments, across every company.';

    public function handle(AlarmService $alarms): int
    {
        Company::all()->each(function (Company $company) use ($alarms) {
            $company->run(function () use ($alarms) {
                $this->sendRemindersFor('reminder_24h', now()->addHours(24), '24 hours', $alarms);
                $this->sendRemindersFor('reminder_1h', now()->addHour(), '1 hour', $alarms);
            });
        });

        return self::SUCCESS;
    }

    /**
     * A +/- 5 minute window around the target time — matches the
     * scheduler's own 5-minute frequency, so no shift is missed or
     * double-caught between two runs.
     */
    private function sendRemindersFor(string $type, Carbon $targetTime, string $label, AlarmService $alarms): void
    {
        $windowStart = $targetTime->copy()->subMinutes(5);
        $windowEnd = $targetTime->copy()->addMinutes(5);

        $assignments = Assignment::where('status', 'confirmed')
            ->whereHas('shift', function ($query) use ($windowStart, $windowEnd) {
                $query->whereBetween('starts_at', [$windowStart, $windowEnd]);
            })
            ->with(['shift', 'worker'])
            ->get();

        foreach ($assignments as $assignment) {
            $alreadySent = Alarm::where('worker_id', $assignment->worker_id)
                ->where('shift_id', $assignment->shift_id)
                ->where('type', $type)
                ->exists();

            if ($alreadySent) {
                continue;
            }

            $alarms->notify(
                $assignment->worker,
                $type,
                "Reminder: your shift \"{$assignment->shift->title}\" starts in {$label}, at {$assignment->shift->starts_at}.",
                $assignment->shift
            );
        }
    }
}
