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

            $table->string('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();

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
