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
        // UNIQUE(employee_id, date) -> only one record per employee per day
        Schema::create('attendance_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->string('shift_code')->nullable(); // (string like “MORNING”, “NIGHT”)
            $table->dateTime('scheduled_start_at')->nullable();
            $table->dateTime('scheduled_end_at')->nullable();
            $table->dateTime('first_in_at')->nullable();
            $table->dateTime('last_out_at')->nullable();
            $table->integer('worked_minutes')->default(0);
            $table->integer('late_minutes')->default(0);
            $table->integer('overtime_minutes')->default(0);
            $table->enum('status', ['PRESENT', 'ABSENT', 'LATE', 'HOLIDAY', 'WEEKEND', 'ON_LEAVE', 'INCOMPLETE']);
            $table->boolean('is_overridden')->default(false);
            $table->foreignId('override_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('override_reason')->nullable(); // (mandatory if overridden)
            $table->json('override_before_json')->nullable();
            $table->json('override_after_json')->nullable();
            $table->dateTime('override_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_days');
    }
};
