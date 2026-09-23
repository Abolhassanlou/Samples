<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete();

            $table->dateTime('assigned_at');
            $table->decimal('transport_amount', 10, 2)->nullable();
            $table->string('status')->default('pending_worker_confirmation'); // pending_worker_confirmation | confirmed | cancelled
            $table->dateTime('confirmed_at')->nullable();

            // Set (and cleared once they re-confirm) when a dispatcher
            // edits the shift's time/location after this worker had
            // already confirmed — a human-readable note of exactly what
            // changed, e.g. "Start time changed from 8:00 PM to 9:00 PM",
            // so the worker isn't left guessing why they're being asked
            // to confirm again. See ShiftController::update().
            $table->text('change_note')->nullable();

            $table->timestamps();

            $table->unique(['shift_id', 'worker_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
