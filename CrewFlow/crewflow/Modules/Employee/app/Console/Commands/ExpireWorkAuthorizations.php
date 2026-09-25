<?php

namespace Modules\Employee\Console\Commands;

use Illuminate\Console\Command;
use Modules\Employee\Models\Worker;

/**
 * Runs once daily (registered in EmployeeServiceProvider::boot() —
 * not routes/console.php, so this module is self-contained and needs
 * no edit to the core app's own scheduling file). Flips
 * work_authorization_status from "valid" to "expired" the moment a
 * worker's own expiry date has passed, across EVERY tenant.
 *
 * Why this matters even though WorkerEligibility already blocks
 * assignment on a past expiry date regardless of what status says: a
 * stale "valid" status is invisible everywhere else — the admin
 * Workers page's status filter, the expiring-documents dashboard list
 * (which lists this worker either way), and any report an admin runs
 * all read the stored status. Without this, a worker who's actually
 * expired would only ever surface at the moment someone tries and
 * fails to assign them, not before.
 *
 * `status` (the *employment* status — active/inactive/etc.) is
 * deliberately untouched — a worker isn't taken off the roster just
 * because a document lapsed, they're just not assignable until it's
 * renewed and an admin re-confirms it.
 */
class ExpireWorkAuthorizations extends Command
{
    protected $signature = 'workers:expire-authorizations';

    protected $description = "Flip work_authorization_status to 'expired' for every worker, across every tenant, whose work_authorization_expiry_date has passed while status was still 'valid'";

    public function handle(): void
    {
        tenancy()->runForMultiple(null, function ($tenant) {
            $count = Worker::where('work_authorization_status', 'valid')
                ->whereNotNull('work_authorization_expiry_date')
                ->whereDate('work_authorization_expiry_date', '<', now()->toDateString())
                ->update(['work_authorization_status' => 'expired']);

            if ($count > 0) {
                $this->info("Tenant {$tenant->getTenantKey()}: expired {$count} worker(s).");
            }
        });
    }
}
