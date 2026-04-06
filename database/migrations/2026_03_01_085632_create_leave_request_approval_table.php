<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_request_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('step');
            $table->enum('role', ['MANAGER', 'HR', 'FINAL']);
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED', 'SKIPPED']);
            $table->foreignId('action_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('action_at')->nullable();
            $table->text('comment')->nullable();
            $table->unique(['leave_request_id', 'step']);
            $table->index('status');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_request_approvals');
    }
};
