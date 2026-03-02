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
        Schema::create('salary_structure_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_structure_id')->constrained()->onDelete('cascade');
            $table->string('code'); // (ex: BASIC, OT, BONUS, PENALTY, ADVANCE)
            $table->json('name');
            $table->enum('type', ['earning', 'deduction']);
            $table->enum('calculation_type', ['fixed', 'percentage', 'manual']); // (manual means it’s entered per payroll period like overtime/bonus/penalty/advance)
            $table->decimal('value', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_structure_items');
    }
};