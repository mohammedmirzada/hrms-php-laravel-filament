<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stamp every punch with the shift the person was on at the time.
     *
     * Without this the reports are measured against whatever the Settings page
     * says today, so moving somebody to another shift silently rewrites last
     * month's hours. With it, a punch keeps the shift it was made under for
     * good, and only punches from now on follow a new assignment.
     *
     * Nullable on purpose: punches recorded before this column existed have no
     * honest answer, so they stay empty and the report falls back to the
     * person's current shift for those.
     */
    public function up(): void
    {
        if (! Schema::hasTable('meraki') || Schema::hasColumn('meraki', 'shift_id')) {
            return;
        }

        Schema::table('meraki', function (Blueprint $table) {
            $table->string('shift_id', 20)->nullable()->after('verify');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('meraki') || ! Schema::hasColumn('meraki', 'shift_id')) {
            return;
        }

        Schema::table('meraki', function (Blueprint $table) {
            $table->dropColumn('shift_id');
        });
    }
};
