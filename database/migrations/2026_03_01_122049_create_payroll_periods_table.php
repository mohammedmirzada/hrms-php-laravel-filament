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
            $table->date('exchange_rate_date'); // processing date used for conversion
            $table->enum('status', ['open', 'attendance_locked', 'calculated', 'approved']);
            $table->foreignId('attendance_locked_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('attendance_locked_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->boolean('immutable')->default(false);
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

/*

✅ 6) payroll_periods

This is the period + locking + approval state.

id

branch_id FK

period_start DATE

period_end DATE

processing_currency_code VARCHAR (IQD)

exchange_rate_date DATE (processing date used for conversion)

status ENUM('open','attendance_locked','calculated','approved')

attendance_locked_by_user_id NULL

attendance_locked_at NULL

approved_by_user_id NULL

approved_at NULL

immutable BOOLEAN DEFAULT false

timestamps

Constraint:

UNIQUE(branch_id,period_start,period_end)

Indexes:

index(branch_id,status)

✅ Lock attendance + immutable after approval.

*/