<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employment_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_worker_id')->constrained()->cascadeOnDelete();

            $table->string('contract_number')->nullable();

            // employment_contract (Echter Dienstvertrag) | free_service_contract
            // (Freier Dienstvertrag) | work_contract (Werkvertrag).
            // Lehrvertrag/Praktikum deliberately excluded for now — add
            // later if actually needed.
            $table->string('contract_type');

            // full_time (Vollzeit) | part_time (Teilzeit) | casual
            // (Fallweise Beschäftigung — important for event staffing:
            // many workers are only booked on specific individual days).
            $table->string('work_time_model');

            // Deliberately its own boolean, not a value inside
            // work_time_model — a worker can be BOTH part_time AND
            // marginal (Geringfügig) at once; they're independent axes.
            $table->boolean('is_marginal')->default(false);

            $table->decimal('weekly_hours', 5, 2)->nullable();

            $table->date('start_date');
            // Null end_date = permanent (Unbefristet); a set end_date =
            // fixed-term (Befristet). Deliberately NOT a separate stored
            // duration_type field — that would risk disagreeing with
            // these actual dates. Derive it when displaying instead.
            $table->date('end_date')->nullable();

            $table->string('status')->default('draft'); // draft | pending_signature | active | expired | terminated | cancelled

            $table->date('termination_date')->nullable();
            $table->string('termination_reason')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employment_contracts');
    }
};
