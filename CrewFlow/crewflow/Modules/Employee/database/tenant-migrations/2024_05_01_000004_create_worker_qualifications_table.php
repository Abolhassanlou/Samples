<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_qualifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('qualification_id')->constrained()->cascadeOnDelete();
            $table->string('source'); // document_verified | company_granted
            $table->foreignId('supporting_document_id')->nullable()->constrained('worker_documents')->nullOnDelete();
            $table->foreignId('granted_by')->constrained('users')->cascadeOnDelete();
            $table->dateTime('granted_at');
            $table->timestamps();

            $table->unique(['worker_id', 'qualification_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_qualifications');
    }
};
