<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employer_compensation', function (Blueprint $table) {
            // salary_structure_id: cascade → restrict (block deletion if compensation records exist)
            $table->dropForeign(['salary_structure_id']);
            $table->foreign('salary_structure_id')->references('id')->on('salary_structures')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('employer_compensation', function (Blueprint $table) {
            $table->dropForeign(['salary_structure_id']);
            $table->foreign('salary_structure_id')->references('id')->on('salary_structures')->onDelete('cascade');
        });
    }
};
