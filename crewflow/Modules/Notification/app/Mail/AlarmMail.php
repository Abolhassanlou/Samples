<?php

namespace Modules\Notification\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Notification\Models\Alarm;

class AlarmMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Alarm $alarm)
    {
    }

    public function build(): self
    {
        return $this->subject($this->subjectFor($this->alarm->type))
            ->text('notification::emails.alarm-plain', ['alarm' => $this->alarm]);
    }

    private function subjectFor(string $type): string
    {
        return match ($type) {
            'shift_assigned' => 'You have been assigned to a new shift',
            'schedule_changed' => 'A shift you are assigned to has changed',
            'location_changed' => 'The location of your shift has changed',
            'cancelled' => 'A shift you were assigned to has been cancelled',
            'reminder_24h' => 'Reminder: your shift starts in 24 hours',
            'reminder_1h' => 'Reminder: your shift starts in 1 hour',
            default => 'Shift update',
        };
    }
}
