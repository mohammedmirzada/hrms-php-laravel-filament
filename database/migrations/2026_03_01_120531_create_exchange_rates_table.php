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
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('base_code', 13); // (USD)
            $table->string('quote_currency', 13); // (IQD)
            $table->decimal('rate', 18, 8);
            $table->date('rate_date');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->unique(['base_code', 'quote_currency', 'rate_date']);
            $table->index('rate_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
