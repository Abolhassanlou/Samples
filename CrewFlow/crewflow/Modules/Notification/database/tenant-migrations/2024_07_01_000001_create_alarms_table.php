<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alarms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->cascadeOnDelete();

            $table->string('type'); // shift_assigned | schedule_changed | location_changed | cancelled
            $table->string('channel')->default('email'); // extensible to push/sms later
            $table->text('message');

            $table->dateTime('sent_at')->nullable();
            $table->dateTime('read_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alarms');
    }
};
