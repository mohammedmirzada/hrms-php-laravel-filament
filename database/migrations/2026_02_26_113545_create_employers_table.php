<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employers', function (Blueprint $table) {
            $table->id();
            $table->json('full_name');
            $table->string('profile_picture')->nullable();
            $table->string('genre');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->string('phone_number_1')->unique();
            $table->string('phone_number_2')->nullable()->unique();
            $table->date('date_of_birth');
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->default('single');
            $table->json('emergency_contact');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('employers')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->date('hire_date');
            $table->date('probation_period_start_date')->nullable();
            $table->date('probation_period_end_date')->nullable();
            $table->date('contract_expiry_date')->nullable();
            $table->foreignId('employment_status_id')->nullable()->constrained()->nullOnDelete();
            $table->index('hire_date');
            $table->index('date_of_birth');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employers');
    }
};
