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
        Schema::create('leave_request_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->constrained()->onDelete('cascade');
            $table->smallInteger('step'); // 1=Manager, 2=HR, 3=Final
            $table->enum('role', ['MANAGER', 'HR', 'FINAL']);
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->onDelete('set null'); //EXAMPLE: who is supposed to act
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED', 'SKIPPED']);
            $table->foreignId('action_by_user_id')->nullable()->constrained('users')->onDelete('set null'); // EXAMPLE: who actually acted
            $table->dateTime('action_at')->nullable();
            $table->text('comment')->nullable();
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
