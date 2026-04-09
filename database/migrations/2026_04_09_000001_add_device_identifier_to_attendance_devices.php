<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_devices', function (Blueprint $table) {
            // ISUP device identifier sent by device during registration (e.g. "DeviceLF1")
            $table->string('device_identifier')->nullable()->unique()->after('name');
            // Shared ISUP key configured on the device; null = skip key validation
            $table->string('isup_key')->nullable()->after('device_identifier');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_devices', function (Blueprint $table) {
            $table->dropColumn(['device_identifier', 'isup_key']);
        });
    }
};
