<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();

            // Legal name split, separate from User.name (a display/login
            // name) — matters for official documents/contracts.
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->date('date_of_birth')->nullable();

            // female | male | diverse
            $table->string('gender')->nullable();
            // single | married | separated | widowed | registered_partnership
            $table->string('marital_status')->nullable();
            $table->string('nationality')->nullable();
            $table->string('native_language')->nullable();
            $table->string('social_security_number')->nullable();
            // How well they speak German specifically — kept separate
            // from `languages_spoken` below since it typically matters
            // most for compliance/work-eligibility purposes.
            // none | basic | conversational | fluent | native
            $table->string('german_language_level')->nullable();
            // Which OTHER languages they speak — a simple multi-select
            // list (no per-language proficiency), e.g. ["english","italian"].
            $table->json('languages_spoken')->nullable();

            // Address — deliberately split into street + house_number
            // (not one combined string) to match how these are actually
            // used separately on official Austrian/German paperwork.
            $table->string('street')->nullable();
            $table->string('house_number')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            // main (Hauptwohnsitz) | secondary (Nebenwohnsitz)
            $table->string('residence_type')->nullable();

            // Bank details. `bank_account_holder_name` is validated
            // (see WorkerRequest) to actually match this worker's own
            // name — nobody can submit someone else's bank account.
            $table->string('bank_name')->nullable();
            $table->string('bank_account_holder_name')->nullable();
            $table->string('iban')->nullable();
            $table->string('bic')->nullable();

            // pending: registered, not yet vetted | active: assignable |
            // inactive: not currently active | blocked: blocked by the company.
            $table->string('status')->default('pending');

            // A summary/current-known status — the authoritative history
            // of the underlying documents lives in worker_documents.
            $table->string('work_authorization_status')->default('pending'); // pending | valid | expired | not_required | rejected
            $table->string('work_authorization_type')->nullable(); // e.g. "Rot-Weiß-Rot Karte Plus"
            $table->date('work_authorization_expiry_date')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workers');
    }
};
