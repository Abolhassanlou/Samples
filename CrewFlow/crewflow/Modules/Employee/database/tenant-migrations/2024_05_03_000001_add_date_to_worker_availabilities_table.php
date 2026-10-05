<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets an availability row belong to one specific calendar date instead
 * of "every week forever". `date` NULL keeps the original meaning — a
 * weekly template row (what admin-panel's create-worker form still
 * writes, via the plain POST /availability endpoint). `date` set means
 * "this worker is available on exactly this date, in this time range";
 * day_of_week is still filled in alongside it (the weekday of that
 * date) so existing day-of-week queries keep working on both kinds.
 *
 * A "repeat for 4 weeks / 4 months / until a date" save is just that
 * many dated rows written at once (see WorkerAvailabilityController::
 * syncWeeks) — not a stored recurrence rule — which is why there's no
 * series to split or "edit this week vs all weeks" to resolve later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('worker_availabilities', function (Blueprint $table) {
            $table->date('date')->nullable()->after('worker_id');
            $table->index(['worker_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('worker_availabilities', function (Blueprint $table) {
            $table->dropIndex(['worker_id', 'date']);
            $table->dropColumn('date');
        });
    }
};
