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
        Schema::create('leave_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained()->onDelete('cascade');
            // Accrual
            $table->boolean('accrual_enabled')->default(false);
            $table->decimal('accrual_rate', 10, 4)->nullable();
            $table->enum('accrual_unit', ['DAY_PER_MONTH', 'HOUR_PER_MONTH', 'DAY_PER_YEAR', 'HOUR_PER_YEAR'])->nullable();
            $table->enum('accrual_start_rule', ['HIRE_DATE', 'AFTER_PROBATION', 'FIXED_DATE'])->nullable();
            $table->string('accrual_start_month_day')->nullable();
            // Carryover
            $table->decimal('annual_cap', 10, 4)->nullable();
            $table->boolean('carryover_enabled')->default(false);
            $table->decimal('carryover_cap', 10, 4)->nullable();
            $table->date('carryover_expiry_date')->nullable();
            // Request rules
            $table->boolean('allow_hourly');
            $table->boolean('allow_half_day');
            $table->integer('min_request_unit_minutes');
            $table->boolean('negative_balance_allowed');
            $table->decimal('negative_balance_limit', 10, 4)->nullable();
            $table->boolean('requires_manager_approval')->default(true);
            $table->boolean('requires_hr_approval')->default(true);
            $table->boolean('requires_final_approval')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_policies');
    }
};
