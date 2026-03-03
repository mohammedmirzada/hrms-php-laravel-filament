<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employment_statuses', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('code')->unique(); // ['Active', 'Terminated', 'Resigned', 'Suspended', 'Probation', 'Contract Expired', 'Future Hired', 'Contract', 'Retired', 'On Leave']
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employment_statuses');
    }
};
