<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained('users')->cascadeOnDelete();
            $table->string('document_type'); // identity_document | residence_permit | work_permit | social_security_card | driving_license | criminal_record | certificate | other
            $table->string('file_path');
            $table->string('document_number')->nullable();
            $table->date('issued_at')->nullable();
            $table->string('visa_type')->nullable();
            $table->date('expires_at')->nullable(); // was visa_expiry_date — generalized, not every document is a visa
            $table->string('review_status')->default('pending'); // pending | approved | rejected | expired
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_documents');
    }
};
