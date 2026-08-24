<?php

namespace Modules\Tenancy\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Service-to-service authentication for the internal Platform API
 * (`/api/internal/*`) — deliberately NOT tied to any per-user session or
 * Sanctum token. This checks only "is this a legitimate call from the
 * Platform service", never "which human is asking" — that authorization
 * happens entirely inside the separate Platform project, before it ever
 * calls this API. crewflow trusts whoever holds the key.
 *
 * This is a first-pass mechanism (a single long, rotatable static key).
 * If multiple services or access levels are ever needed, swap this for
 * OAuth2 Client Credentials or HMAC-signed requests instead — the rest
 * of this module doesn't need to change either way, since every
 * consuming controller only cares that this middleware already ran.
 */
class AuthenticatePlatformService
{
    public function handle(Request $request, Closure $next): Response
    {
        $provided = $request->bearerToken();
        $expected = config('tenancy_module.platform_service_api_key');

        if (! $expected) {
            abort(500, 'PLATFORM_SERVICE_API_KEY is not configured.');
        }

        if (! $provided || ! hash_equals($expected, $provided)) {
            abort(401, 'Invalid or missing service credentials.');
        }

        return $next($request);
    }
}
