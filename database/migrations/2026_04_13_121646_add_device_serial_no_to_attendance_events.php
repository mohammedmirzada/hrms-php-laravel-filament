<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_events', function (Blueprint $table) {
            $table->unsignedBigInteger('device_serial_no')->nullable()->after('device_user_code');
            $table->unique(['device_id', 'device_serial_no'], 'unique_device_serial');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_events', function (Blueprint $table) {
            $table->dropUnique('unique_device_serial');
            $table->dropColumn('device_serial_no');
        });
    }
};
