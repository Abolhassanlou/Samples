<?php

namespace Modules\Notification\Observers;

use Modules\Notification\Services\AlarmService;
use Modules\Shift\Models\Assignment;

class AssignmentObserver
{
    public function __construct(private AlarmService $alarms)
    {
    }

    public function created(Assignment $assignment): void
    {
        $worker = $assignment->worker;
        $shift = $assignment->shift;

        $this->alarms->notify(
            $worker,
            'shift_assigned',
            "You have been assigned to the shift \"{$shift->title}\" starting {$shift->starts_at}. Please confirm in the app.",
            $shift
        );
    }
}
