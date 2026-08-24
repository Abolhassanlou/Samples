<?php

namespace Modules\Transaction\Console\Commands;

use Illuminate\Console\Command;
use Modules\Tenancy\Models\Company;
use Modules\Transaction\Models\RecurringBillingProfile;
use Modules\Transaction\Models\Transaction;

/**
 * Meant to run once a day (see TransactionServiceProvider) via Laravel's
 * scheduler. Walks every company (same pattern as Notification's
 * SendShiftReminders) and, within each one, creates a new pending
 * Transaction for every active RecurringBillingProfile whose
 * next_billing_date has arrived, then advances that date to the next
 * cycle (weekly or monthly).
 */
class GenerateRecurringInvoices extends Command
{
    protected $signature = 'transaction:generate-recurring-invoices';

    protected $description = 'Create a pending Transaction for every client with a due recurring billing profile, across every company.';

    public function handle(): int
    {
        Company::all()->each(function (Company $company) {
            $company->run(function () {
                $dueProfiles = RecurringBillingProfile::where('is_active', true)
                    ->where('next_billing_date', '<=', now()->toDateString())
                    ->get();

                foreach ($dueProfiles as $profile) {
                    Transaction::create([
                        'client_id' => $profile->client_id,
                        'shift_id' => null,
                        'amount' => $profile->amount,
                        'status' => 'pending',
                        'description' => "Recurring {$profile->cycle} invoice.",
                        'due_at' => $profile->next_billing_date,
                        'created_by' => null,
                    ]);

                    $profile->advanceNextBillingDate();
                }
            });
        });

        return self::SUCCESS;
    }
}
