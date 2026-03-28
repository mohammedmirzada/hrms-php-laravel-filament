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
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('cascade');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('processing_currency_code', 13); // (IQD/USD)
            $table->date('exchange_rate_date')->nullable(); // null when processing currency needs no conversion
            $table->enum('status', ['open', 'calculated', 'approved']);
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->boolean('immutable')->default(false);
            $table->unique(['branch_id', 'period_start', 'period_end']);
            $table->index(['branch_id', 'status']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_periods');
    }
};