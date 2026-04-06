<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_structure_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_structure_id')->constrained()->cascadeOnDelete();
            $table->json('name');
            $table->enum('type', ['earning', 'deduction']);
            $table->enum('calculation_type', ['fixed', 'percentage']);
            $table->decimal('value', 15, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_structure_items');
    }
};
