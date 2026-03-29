<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_policies', function (Blueprint $table) {
            $table->dropColumn(['negative_balance_allowed', 'negative_balance_limit']);
        });
    }

    public function down(): void
    {
        Schema::table('leave_policies', function (Blueprint $table) {
            $table->boolean('negative_balance_allowed')->default(false);
            $table->decimal('negative_balance_limit', 10, 4)->nullable();
        });
    }
};
