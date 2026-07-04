<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Brings an EXISTING database in line with the Lionsfort hardcoded model,
 * so you can run `php artisan migrate` without `migrate:fresh` (no data loss).
 *
 * Every step is guarded, so on a fresh install — where the create-migrations
 * already produce the final shape — this migration is a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Per-employee fixed working hours (replaces the shift system).
        Schema::table('employers', function (Blueprint $table) {
            if (! Schema::hasColumn('employers', 'work_start_time')) {
                $table->time('work_start_time')->nullable();
            }
            if (! Schema::hasColumn('employers', 'work_end_time')) {
                $table->time('work_end_time')->nullable();
            }
        });

        // 2. attendance_events: drop the now-unused device_id (single hardcoded
        //    device lives in config/attendance.php). Existing DBs only.
        if (Schema::hasColumn('attendance_events', 'device_id')) {
            Schema::table('attendance_events', function (Blueprint $table) {
                $table->dropForeign(['device_id']);
                $table->dropUnique('unique_device_serial');   // was (device_id, device_serial_no)
                $table->dropColumn('device_id');
            });
            Schema::table('attendance_events', function (Blueprint $table) {
                $table->unique('device_serial_no', 'unique_device_serial');
            });
        }

        // 3. Drop the dynamic-config tables that were removed. dropIfExists is a
        //    no-op on a fresh DB (those tables were never created).
        //    --- comment this block out if you want to keep the old data around ---
        Schema::disableForeignKeyConstraints();

        foreach ([
            'employer_compensation',
            'salary_structure_items',
            'salary_structures',
            'social_security_rules',
            'payroll_periods',
            'exchange_rates',
            'employer_shifts',
            'shifts',
            'leave_request_approval',
            'leave_ledger_entries',
            'leave_balances',
            'leave_requests',
            'leave_policies',
            'leave_types',
            'attendance_devices',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // One-way cleanup migration — the removed tables/columns are not restored.
        // Use git + `migrate:fresh` to rebuild the old schema if ever needed.
    }
};
