<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            // strict (default): the normal ShiftVisibility rule — hide
            // entirely from anyone who doesn't qualify for at least one
            // position. override: bypass the qualification check
            // entirely, no warning, visible to everyone (a deliberate
            // staffing-shortage escape hatch). warn: also visible to
            // everyone and they CAN act (express interest/be assigned),
            // but a dispatcher reviewing that interest/assignment sees a
            // flag that this specific worker doesn't actually meet the
            // requirement — see ShiftVisibility::workerQualifies().
            $table->string('qualification_policy')->default('strict')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn('qualification_policy');
        });
    }
};
