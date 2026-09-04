<?php

namespace Modules\Employee\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * The link points to a config-driven worker-portal URL (see
 * config/config.php's `worker_portal_url`) — that portal doesn't exist
 * yet (see this module's README for the invitation flow's current
 * scope). Sending this out and confirming it lands correctly (e.g. in
 * storage/logs/laravel.log with MAIL_MAILER=log) is deliberately step
 * one, before building anything the link actually opens.
 */
class WorkerInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $inviteUrl, public string $companyName, public string $companyCode)
    {
    }

    public function build(): self
    {
        return $this->subject("You're invited to join {$this->companyName} on CrewFlow")
            ->text('employee::emails.worker-invitation-plain', [
                'inviteUrl' => $this->inviteUrl,
                'companyName' => $this->companyName,
                'companyCode' => $this->companyCode,
            ]);
    }
}
