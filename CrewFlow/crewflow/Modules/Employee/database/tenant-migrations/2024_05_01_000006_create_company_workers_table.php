<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_workers', function (Blueprint $table) {
            $table->id();
            // Unique: one relationship row per worker, since this
            // database already belongs to exactly one company — no
            // company_id column needed (see this module's README).
            $table->foreignId('worker_id')->unique()->constrained('workers')->cascadeOnDelete();

            // A manually-assigned, company-specific employee number —
            // deliberately separate from Authentication's auto-assigned
            // User.personnel_number. That one exists purely to tell two
            // same-named users apart in a list; this one follows
            // whatever numbering convention this particular company
            // actually uses (e.g. "MA-0048"), and is optional.
            $table->string('employee_number')->nullable();

            $table->foreignId('home_branch_id')->nullable()->constrained('branches')->nullOnDelete();

            // A declared preference, not inferred from availability slots
            // — a dispatcher assigning a night shift needs a direct
            // yes/no. Lives here (the employment relationship level),
            // not on Worker (pure personal facts) or EmploymentContract
            // (contract terms) — it's about how this worker engages with
            // this company, independent of which specific contract is active.
            $table->boolean('works_night_shifts')->default(false);

            // invited: told to join, hasn't yet | pending: joined, not
            // yet approved | active: currently working | inactive: not
            // currently active | blocked: blocked by the company.
            $table->string('status')->default('invited');

            $table->date('joined_at')->nullable();
            $table->date('left_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_workers');
    }
};
