<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->string('location_type')->default('on_site'); // on_site | online
            $table->string('location_address')->nullable();
            $table->decimal('location_lat', 10, 7)->nullable();
            $table->decimal('location_lng', 10, 7)->nullable();

            $table->string('client_contact_name')->nullable();
            $table->string('client_contact_phone')->nullable();
            $table->string('internal_contact_name')->nullable();
            $table->string('internal_contact_phone')->nullable();

            $table->unsignedInteger('quantity_needed')->default(1);

            $table->string('rate_type')->default('hourly'); // hourly | fixed
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->decimal('fixed_amount', 10, 2)->nullable();
            $table->decimal('client_billing_rate', 10, 2)->nullable(); // hidden from workers at the API layer

            $table->dateTime('starts_at');
            $table->dateTime('ends_at');

            $table->string('status')->default('open'); // open | filled | in_progress | completed | cancelled

            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
