<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained('employers')->onDelete('cascade');
            $table->enum('document_type', ['ID Card', 'Passport', 'Driver License', 'Work Permit', 'Visa', 'Contract', 'Certificate', 'Degree', 'CV', 'Other']);
            $table->string('file_path');
            $table->date('expiry_date')->nullable();
            $table->index('expiry_date');
            $table->index('document_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
