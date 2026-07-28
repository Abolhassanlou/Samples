<?php

return [
    'name' => 'Tenancy',

    /*
     * The root domain new company subdomains are appended to.
     * e.g. base_domain=crewflow.localhost -> a company with company_code
     * "acme2024" gets the domain "acme2024.crewflow.localhost".
     */
    'base_domain' => env('APP_BASE_DOMAIN', 'crewflow.localhost'),
];
