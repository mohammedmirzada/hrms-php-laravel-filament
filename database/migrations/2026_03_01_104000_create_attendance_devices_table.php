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
        Schema::create('attendance_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->enum('vendor', ['ZKTECO', 'SUPREMA']);
            $table->string('name');
            $table->string('ip_address')->nullable();
            $table->integer('port')->nullable();
            $table->string('serial_number')->nullable();
            $table->enum('sync_mode', ['REALTIME', 'SCHEDULED']);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_devices');
    }
};
