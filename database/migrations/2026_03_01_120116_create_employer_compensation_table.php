<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employer_compensation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained('employers')->cascadeOnDelete();
            $table->foreignId('salary_structure_id')->constrained('salary_structures')->restrictOnDelete();
            $table->string('currency_code', 13);
            $table->decimal('basic_salary', 18, 4);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->index(['employer_id', 'effective_from']);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employer_compensation');
    }
};
