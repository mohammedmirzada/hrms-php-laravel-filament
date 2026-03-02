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
        Schema::create('attendance_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('device_id')->nullable()->constrained()->onDelete('set null');
            $table->string('device_user_code')->nullable();
            $table->enum('source', ['BIOMETRIC', 'MOBILE', 'MANUAL']);
            $table->enum('event_type', ['IN', 'OUT'])->nullable();
            $table->dateTime('event_at');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('accuracy_m')->nullable();
            $table->string('selfie_path')->nullable();
            $table->json('raw_payload')->nullable();
            $table->boolean('is_valid')->default(true);
            $table->string('invalid_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_events');
    }
};
