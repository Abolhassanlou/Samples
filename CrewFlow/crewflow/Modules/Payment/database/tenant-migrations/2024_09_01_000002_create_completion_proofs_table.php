<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('completion_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->unique()->constrained('assignments')->cascadeOnDelete();
            $table->string('proof_type'); // uploaded_document | digital_signature
            $table->string('file_path')->nullable();
            $table->longText('signature_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('completion_proofs');
    }
};
