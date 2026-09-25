<?php

namespace Modules\Employee\Console\Commands;

use Illuminate\Console\Command;
use Modules\Employee\Models\EmploymentContract;

/**
 * Runs once daily (registered in EmployeeServiceProvider — see that
 * class's registerSchedule(), same pattern as ExpireWorkAuthorizations
 * right next to this file). Flips a contract's stored `status` from
 * "active" to "expired" the moment its end_date has passed, across
 * every tenant.
 *
 * Why this matters even though WorkerEligibility already treats a
 * past-end_date contract as not-currently-active regardless of what
 * `status` says (see EmploymentContract::isCurrentlyActive() — it
 * checks BOTH status AND the date, so assignment safety was never at
 * risk): a stale "active" status is invisible everywhere else — the
 * admin's own Contracts table on a worker's profile, any report or
 * export, anyone just glancing at the badge. Without this, a lapsed
 * contract looks identical to a genuinely active one until someone
 * actually tries and fails to assign that worker.
 *
 * A permanent contract (end_date IS NULL) is never touched — there's
 * nothing to expire.
 */
class ExpireContracts extends Command
{
    protected $signature = 'contracts:expire';

    protected $description = "Flip EmploymentContract.status to 'expired' for every contract, across every tenant, whose end_date has passed while status was still 'active'";

    public function handle(): void
    {
        tenancy()->runForMultiple(null, function ($tenant) {
            $count = EmploymentContract::where('status', 'active')
                ->whereNotNull('end_date')
                ->whereDate('end_date', '<', now()->toDateString())
                ->update(['status' => 'expired']);

            if ($count > 0) {
                $this->info("Tenant {$tenant->getTenantKey()}: expired {$count} contract(s).");
            }
        });
    }
}
