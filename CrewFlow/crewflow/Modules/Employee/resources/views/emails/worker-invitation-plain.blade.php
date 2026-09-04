{{-- Deliberately {!! !!} (raw, unescaped) for the URL, not {{ }} — this
     is a text/plain email, never rendered as HTML, so Blade's default
     htmlspecialchars() escaping only corrupts the URL's own "&" into
     "&amp;". {{ }} stays fine for the plain text fields above it. --}}
You've been invited to join {{ $companyName }} ({{ $companyCode }}) on CrewFlow.

Set up your account here: {!! $inviteUrl !!}

This link will expire in 7 days.
