<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employer_id')->nullable()->constrained('employers')->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('attendance_devices')->nullOnDelete();
            $table->string('device_user_code')->nullable();
            $table->enum('source', ['BIOMETRIC', 'MOBILE']);
            $table->enum('event_type', ['IN', 'OUT'])->nullable();
            $table->dateTime('event_at');
            $table->string('selfie_path')->nullable();
            $table->json('raw_payload')->nullable();
            $table->boolean('is_valid')->default(true);
            $table->string('invalid_reason')->nullable();
            $table->index('event_at');
            $table->index(['employer_id', 'event_at']);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_events');
    }
};
