<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            $table->string('default_recurrence_mode')->default('reconfirm_each_time');
            $table->string('shift_completion_mode')->default('button_confirm');
            $table->string('shift_visibility_mode')->default('show_disabled');
            $table->unsignedInteger('warning_hour_threshold')->nullable();
            $table->decimal('warning_income_threshold', 12, 2)->nullable();
            $table->boolean('gps_checkin_required')->default(false);

            $table->timestamps();

            $table->unique('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
