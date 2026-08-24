<?php

namespace Modules\Notification\Observers;

use Illuminate\Support\Collection;
use Modules\Authentication\Models\User;
use Modules\Notification\Services\AlarmService;
use Modules\Shift\Models\Shift;

class ShiftObserver
{
    public function __construct(private AlarmService $alarms)
    {
    }

    public function updated(Shift $shift): void
    {
        $confirmedWorkerIds = $shift->assignments()
            ->where('status', 'confirmed')
            ->pluck('worker_id');

        if ($confirmedWorkerIds->isEmpty()) {
            return;
        }

        if ($shift->wasChanged('status') && $shift->status === 'cancelled') {
            $this->notifyAll($shift, $confirmedWorkerIds, 'cancelled', "The shift \"{$shift->title}\" has been cancelled.");

            return;
        }

        if ($shift->wasChanged(['starts_at', 'ends_at'])) {
            $this->notifyAll($shift, $confirmedWorkerIds, 'schedule_changed', "The time for shift \"{$shift->title}\" has changed. New time: {$shift->starts_at} - {$shift->ends_at}.");
        }

        if ($shift->wasChanged('location_address')) {
            $this->notifyAll($shift, $confirmedWorkerIds, 'location_changed', "The location for shift \"{$shift->title}\" has changed to: {$shift->location_address}.");
        }
    }

    private function notifyAll(Shift $shift, Collection $workerIds, string $type, string $message): void
    {
        $workers = User::whereIn('id', $workerIds)->get();

        foreach ($workers as $worker) {
            $this->alarms->notify($worker, $type, $message, $shift);
        }
    }
}
