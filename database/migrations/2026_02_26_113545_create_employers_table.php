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
        Schema::create('employers', function (Blueprint $table) {
            $table->id();
            $table->json('full_name');
            $table->string('genre');
            $table->string('email')->unique();
            $table->string('phone_number_1')->unique();
            $table->string('phone_number_2')->nullable()->unique();
            $table->date('date_of_birth');
            $table->string('marital_status')->default('single')->enum('single', 'married', 'divorced', 'widowed');
            $table->json('emergency_contact');
            $table->foreignId('department_id')->nullable()->constrained();
            $table->foreignId('position_id')->nullable()->constrained();
            $table->foreignId('manager_id')->nullable()->constrained();
            $table->date('hire_date');
            $table->date('probation_period_start_date')->nullable();
            $table->date('probation_period_end_date')->nullable();
            $table->date('contract_expiry_date')->nullable();
            $table->foreignId('employment_status_id')
                ->nullable()->constrained()->default(1)
                ->comment('1: Active, 2: Terminated, 3: Resigned, 4: Suspended, 5: Probation, 6: Contract Expired, 7: Future Hired, 8: Contract, 9: Retired, 10: On Leave');
            $table->foreignId('salary_structure_id')->nullable()->constrained();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employers');
    }
};

// salary_structure_items
// ----------------------
// id
// salary_structure_id
// name
// type                (earning/deduction)
// calculation_type    (fixed/percentage)
// value
// created_at
// updated_at