<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained('employers')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_request_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('entry_type', ['ACCRUAL', 'DEDUCTION', 'ADJUSTMENT', 'REVERSAL', 'EXPIRY']);
            $table->integer('amount_minutes');
            $table->date('occurred_on');
            $table->text('note')->nullable();
            $table->index('occurred_on');
            $table->index('entry_type');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_ledger_entries');
    }
};
