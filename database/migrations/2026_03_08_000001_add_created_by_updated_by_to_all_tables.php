<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that need both created_by and updated_by added.
     */
    protected array $tablesNeedingBoth = [
        'departments',
        'positions',
        'branches',
        'employment_statuses',
        'salary_structures',
        'employers',
        'documents',
        'salary_structure_items',
        'leave_types',
        'leave_policies',
        'leave_request_approvals',
        'leave_balances',
        'holidays',
        'attendance_devices',
        'attendance_events',
        'attendance_branch_settings',
        'employer_compensation',
        'social_security_rules',
        'payroll_periods',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add created_by & updated_by to tables that have neither
        foreach ($this->tablesNeedingBoth as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            });
        }

        // 2. Rename created_by_user_id → created_by on leave_requests + add updated_by
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_user_id');
        });
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });

        // 3. Rename created_by_user_id → created_by on leave_ledger_entries + add updated_by
        Schema::table('leave_ledger_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_user_id');
        });
        Schema::table('leave_ledger_entries', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });

        // 4. Add updated_by to exchange_rates (already has created_by)
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse exchange_rates
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
        });

        // Reverse leave_ledger_entries
        Schema::table('leave_ledger_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('updated_by');
        });
        Schema::table('leave_ledger_entries', function (Blueprint $table) {
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
        });

        // Reverse leave_requests
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('updated_by');
        });
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
        });

        // Reverse all tables that got both columns
        foreach ($this->tablesNeedingBoth as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropConstrainedForeignId('created_by');
                $table->dropConstrainedForeignId('updated_by');
            });
        }
    }
};
