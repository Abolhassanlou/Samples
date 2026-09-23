{{-- Deliberately {!! !!} (raw, unescaped) for the URL, not {{ }} — this
     is a text/plain email, never rendered as HTML, so Blade's default
     htmlspecialchars() escaping only corrupts the URL's own "&" into
     "&amp;" (same reasoning as the Employee module's invitation email). --}}
Someone requested a password reset for your CrewFlow account.

Set a new password here: {!! $resetUrl !!}

This link will expire in 1 hour. If you didn't request this, you can safely ignore this email — your password hasn't been changed.
