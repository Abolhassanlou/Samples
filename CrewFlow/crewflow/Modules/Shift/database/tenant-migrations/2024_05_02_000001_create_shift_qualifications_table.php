<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_qualifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();

            // Nullable: when set, this requirement applies ONLY to that
            // one role/position (e.g. a combined "Math & English" class
            // needs a Math qualification for the Math-teacher position
            // and a completely different English one for the
            // English-teacher position — they must be independent, not
            // both required of anyone assigned to either role). When
            // null, it's a shift-wide requirement — the only option for
            // a Shift with no positions at all (plain quantity_needed).
            $table->foreignId('shift_position_id')->nullable()->constrained()->cascadeOnDelete();

            $table->foreignId('qualification_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // Deliberately NOT unique on (shift_id, qualification_id)
            // alone anymore — the same qualification can legitimately be
            // required by two different positions on the same shift.
            $table->unique(['shift_position_id', 'qualification_id'], 'shift_qualifications_position_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_qualifications');
    }
};
