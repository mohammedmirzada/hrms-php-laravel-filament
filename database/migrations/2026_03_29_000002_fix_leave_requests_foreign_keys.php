<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            // policy_id: cascade → set null (request survives policy deletion)
            $table->dropConstrainedForeignId('policy_id');
        });
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->foreignId('policy_id')->nullable()->constrained('leave_policies')->nullOnDelete()->change();
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            // branch_id: cascade → restrict (block branch deletion if requests exist)
            $table->dropForeign(['branch_id']);
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('restrict');
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            // leave_type_id: cascade → restrict (block leave type deletion if requests exist)
            $table->dropForeign(['leave_type_id']);
            $table->foreign('leave_type_id')->references('id')->on('leave_types')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');

            $table->dropForeign(['leave_type_id']);
            $table->foreign('leave_type_id')->references('id')->on('leave_types')->onDelete('cascade');
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropForeign(['policy_id']);
        });
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->foreignId('policy_id')->constrained('leave_policies')->onDelete('cascade')->change();
        });
    }
};
