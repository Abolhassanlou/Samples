<?php

return [
    'name' => 'Tenancy',

    /*
     * The root domain new company subdomains are appended to.
     * e.g. base_domain=crewflow.localhost -> a company with company_code
     * "acme2024" gets the domain "acme2024.crewflow.localhost".
     */
    'base_domain' => env('APP_BASE_DOMAIN', 'crewflow.localhost'),

    /*
     * Every self-registered company automatically starts on a trial of
     * this plan (matched by name) for this many days. If no plan with
     * this name exists, the company is simply left without a subscription
     * — registration still succeeds either way.
     */
    'default_trial_plan' => env('DEFAULT_TRIAL_PLAN', 'Demo'),
    'default_trial_days' => env('DEFAULT_TRIAL_DAYS', 14),
];
