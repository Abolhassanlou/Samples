<?php

namespace Modules\Authentication\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Sent only by AuthController::forgotPassword() — self-service, works
 * for any user regardless of Worker/CompanyWorker status (Authentication
 * doesn't know about those; see that method's docblock for why an
 * inactive/blocked worker resetting their own password is harmless).
 * The reset link's base URL comes from whichever frontend made the
 * request (admin panel or worker portal each pass their own
 * `redirect_url`), not a fixed config value — this module has no way to
 * know in advance which of the two apps a given request came from.
 */
class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $resetUrl)
    {
    }

    public function build(): self
    {
        return $this->subject('Reset your CrewFlow password')
            ->text('authentication::emails.password-reset-plain', [
                'resetUrl' => $this->resetUrl,
            ]);
    }
}
