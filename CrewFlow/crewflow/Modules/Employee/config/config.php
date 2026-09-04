<?php

return [
    'name' => 'Employee',

    /*
     * Where the worker-invitation link points. The worker-facing portal
     * doesn't exist yet — this is deliberately configurable so the
     * invitation email/link mechanism can be built and tested (e.g. via
     * MAIL_MAILER=log) before that portal is built. Update this once it
     * exists.
     */
    'worker_portal_url' => env('WORKER_PORTAL_URL', 'http://localhost:5174/accept-invite'),
];
