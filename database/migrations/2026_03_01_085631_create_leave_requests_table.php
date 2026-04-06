<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('leave_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('policy_id')->nullable()->constrained('leave_policies')->nullOnDelete();
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
            $table->index('status');
            $table->index(['employer_id', 'status']);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
