<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenancy\Http\Controllers\Api\CompanyRegistrationController;

// Central-only route: no tenant middleware here on purpose. This must be
// reachable from the central domain(s) (e.g. crewflow.localhost), before
// any specific company's subdomain exists.
Route::post('companies/register', [CompanyRegistrationController::class, 'register']);
