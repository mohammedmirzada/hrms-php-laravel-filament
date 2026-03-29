<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bug 2: fix leave_types document_type enum and make nullable
        Schema::table('leave_types', function (Blueprint $table) {
            $table->enum('document_type', ['Medical Certificate', 'Death Certificate', 'Marriage Certificate', 'Court Order', 'Travel Document', 'Other'])
                ->nullable()
                ->change();
        });

        // Bug 4: remove default(1) from employment_status_id
        // Indexes for manager_id and employment_status_id already exist from create_employers_table migration
        Schema::table('employers', function (Blueprint $table) {
            $table->unsignedBigInteger('employment_status_id')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->enum('document_type', ['ID Card', 'Passport', 'Driver License', 'Work Permit', 'Visa', 'Contract', 'Certificate', 'Degree', 'CV', 'Other'])
                ->nullable(false)
                ->change();
        });

        Schema::table('employers', function (Blueprint $table) {
            $table->unsignedBigInteger('employment_status_id')->nullable()->default(1)->change();
        });
    }
};
