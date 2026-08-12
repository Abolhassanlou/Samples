<?php

namespace Modules\Notification\Services;

use Illuminate\Support\Facades\Mail;
use Modules\Authentication\Models\User;
use Modules\Notification\Mail\AlarmMail;
use Modules\Notification\Models\Alarm;
use Modules\Shift\Models\Shift;

class AlarmService
{
    public function notify(User $worker, string $type, string $message, ?Shift $shift = null): Alarm
    {
        $alarm = Alarm::create([
            'worker_id' => $worker->id,
            'shift_id' => $shift?->id,
            'type' => $type,
            'channel' => 'email',
            'message' => $message,
        ]);

        Mail::to($worker->email)->send(new AlarmMail($alarm));

        $alarm->update(['sent_at' => now()]);

        return $alarm;
    }
}
