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
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('policy_id')->constrained()->onDelete('cascade');
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->integer('duration_minutes');
            $table->decimal('duration_days', 10, 2)->nullable();
            $table->enum('day_part', ['FULL_DAY', 'HALF_DAY_AM', 'HALF_DAY_PM', 'HOURLY']);
            $table->text('reason')->nullable();
            $table->string('attachment_path')->nullable();
            $table->enum('status', [
                'DRAFT',
                'SUBMITTED',
                'MANAGER_APPROVED',
                'HR_APPROVED',
                'FINAL_APPROVED',
                'REJECTED',
                'CANCELLED'
            ])->default('DRAFT');
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->dateTime('canceled_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
