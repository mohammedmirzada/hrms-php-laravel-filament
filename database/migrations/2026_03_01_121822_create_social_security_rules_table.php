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
        Schema::create('social_security_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('employment_type', ['full_time', 'part_time', 'contract'])->nullable();
            $table->decimal('employee_percent', 9, 4);
            $table->decimal('employer_percent', 9, 4);
            $table->enum('base_rule', ['basic_only', 'basic_plus_marked', 'gross']);
            $table->boolean('cap_enabled')->default(false);
            $table->decimal('cap_amount', 18, 4)->nullable();
            $table->string('currency_code', 13); // (IQD/USD)
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_security_rules');
    }
};