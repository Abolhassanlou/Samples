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

            // Nullable, and deliberately NOT a belongsTo relationship on
            // the model (no PHP import of Shift's Event — Shift already
            // depends on Employee, so importing the other way would be
            // circular; this stays a plain FK column only, safe because
            // Shift's `events` table migration runs earlier). Left null
            // for an ongoing relationship contract (e.g. a ongoing role
            // like teaching — signed once, covers everything). Set for a
            // work_contract tied to one specific event/project — some
            // companies need a fresh contract per event, others don't;
            // this one column supports both without two separate systems.
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();

            $table->string('contract_number')->nullable();

            // employment_contract (Echter Dienstvertrag) | free_service_contract
            // (Freier Dienstvertrag) | work_contract (Werkvertrag) |
            // assignment_notice (Überlassungsmitteilung — Austrian
            // staff-leasing notification, auto-created per Event when
            // that Event.requires_contract is true; see Shift's
            // AssignmentController). Lehrvertrag/Praktikum deliberately
            // excluded for now — add later if actually needed.
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

            // The actual contract document (PDF, usually) — an admin
            // uploads this when creating/editing the contract. Without
            // this, "signing" would just be a worker clicking a button
            // with nothing to actually read first, which defeats the
            // point. Nullable because a draft might not have a file
            // attached yet, and some very informal `casual` contracts
            // might never need one.
            $table->string('file_path')->nullable();

            // Set only when the WORKER themselves confirms/signs — see
            // EmploymentContractController::sign(). Distinct from
            // created_at/updated_at, which change on any admin edit too.
            $table->dateTime('signed_at')->nullable();

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
