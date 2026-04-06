<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->index(['subject_type', 'subject_id', 'created_at'], 'activity_log_subject_timeline');
            $table->index(['log_name', 'created_at'], 'activity_log_category_date');
            $table->index(['causer_type', 'causer_id', 'created_at'], 'activity_log_causer_date');
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex('activity_log_subject_timeline');
            $table->dropIndex('activity_log_category_date');
            $table->dropIndex('activity_log_causer_date');
        });
    }
};
