<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_events', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'accuracy_m']);
        });
    }

    public function down(): void
    {
        Schema::table('attendance_events', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('event_at');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->integer('accuracy_m')->nullable()->after('longitude');
        });
    }
};
