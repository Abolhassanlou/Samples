<?php

namespace Modules\Tenancy\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Core\Database\Seeders\CoreDatabaseSeeder;
use Modules\Core\Models\User;
use Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder;
use Modules\Tenancy\Http\Requests\RegisterCompanyRequest;
use Modules\Tenancy\Models\Company;

class CompanyRegistrationController extends Controller
{
    /**
     * Register a brand new company (tenant).
     *
     * This is the ONLY endpoint in the whole system that runs purely in
     * the Central context — there is no existing tenant to resolve yet.
     * Every other endpoint (including Core's worker registration) expects
     * tenancy to already be initialized via the subdomain middleware.
     *
     * Flow:
     *  1. Generate a unique, subdomain-safe company_code from the company name.
     *  2. Create the Company (tenant) record. This fires TenantCreated,
     *     which (per TenancyServiceProvider) synchronously creates AND
     *     migrates a brand new database for this tenant.
     *  3. Create a Domain record: {company_code}.{base_domain}.
     *  4. Switch into the new tenant's context just long enough to seed
     *     default roles/permissions, create the default "Main" branch, and
     *     create the first Company Admin user.
     *
     * Architectural note: this controller deliberately references both the
     * Core and Organization modules' seeders. That is an intentional,
     * narrow exception to the project's usual dependency direction
     * (Tenancy → Core only) — this class is a company-onboarding
     * *orchestrator*, not ordinary business logic, and orchestrating a new
     * tenant's initial data inherently touches every tenant-scoped module.
     */
    public function register(RegisterCompanyRequest $request)
    {
        $companyCode = $this->generateUniqueCompanyCode($request->validated('company_name'));

        $company = Company::create([
            'id' => (string) Str::uuid(),
            'company_code' => $companyCode,
            'name' => $request->validated('company_name'),
        ]);

        $domain = $companyCode.'.'.config('tenancy_module.base_domain');

        // IMPORTANT: with InitializeTenancyBySubdomain, the `domain` column must
        // contain ONLY the subdomain label (no dots) — e.g. "acme-security",
        // not "acme-security.crewflow.localhost". A value containing a dot is
        // treated by the resolver as a full hostname instead of a subdomain
        // label, and would silently fail to match.
        $company->domains()->create([
            'domain' => $companyCode,
        ]);

        $company->run(function () use ($request) {
            (new CoreDatabaseSeeder())->run();
            (new OrganizationDatabaseSeeder())->run();

            $admin = User::create([
                'name' => $request->validated('admin_name'),
                'email' => $request->validated('admin_email'),
                'phone' => $request->validated('admin_phone'),
                'password' => Hash::make($request->validated('admin_password')),
            ]);

            $admin->assignRole('Company Admin');
        });

        return response()->json([
            'success' => true,
            'message' => 'Company registered successfully',
            'data' => [
                'company_code' => $companyCode,
                'domain' => $domain,
                'login_url' => 'https://'.$domain.'/api/auth/login',
            ],
        ], 201);
    }

    private function generateUniqueCompanyCode(string $companyName): string
    {
        $base = Str::slug($companyName);

        if ($base === '') {
            $base = 'company';
        }

        $code = $base;
        $attempts = 0;

        while (Company::where('company_code', $code)->exists()) {
            $attempts++;
            $code = $base.'-'.Str::random($attempts > 20 ? 8 : 4);
        }

        return $code;
    }
}
