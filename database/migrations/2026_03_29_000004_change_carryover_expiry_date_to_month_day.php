<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing full-date values (e.g. "2026-03-31") cannot fit in varchar(5).
        // Null them out — admin must re-enter as MM-DD after migration.
        \Illuminate\Support\Facades\DB::table('leave_policies')
            ->whereNotNull('carryover_expiry_date')
            ->update(['carryover_expiry_date' => null]);

        Schema::table('leave_policies', function (Blueprint $table) {
            // Change from full DATE to "MM-DD" string so it repeats annually
            $table->string('carryover_expiry_date', 5)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('leave_policies', function (Blueprint $table) {
            $table->date('carryover_expiry_date')->nullable()->change();
        });
    }
};
