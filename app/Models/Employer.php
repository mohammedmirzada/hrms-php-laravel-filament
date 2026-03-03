<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employer extends Model {

    use SoftDeletes;

    protected $fillable = [
        'full_name',
        'genre',
        'email',
        'phone_number_1',
        'phone_number_2',
        'date_of_birth',
        'marital_status',
        'emergency_contact',
        'department_id',
        'position_id',
        'manager_id',
        'hire_date',
        'probation_period_start_date',
        'probation_period_end_date',
        'contract_expiry_date',
        'employment_status_id',
        'salary_structure_id'
    ];

    protected $casts = [
        'full_name' => 'array',
        'emergency_contact' => 'array'
    ];

    public function department() {
        return $this->belongsTo(Department::class);
    }

    public function position() {
        return $this->belongsTo(Position::class);
    }

    public function manager() {
        return $this->belongsTo(Employer::class, 'manager_id', 'id');
    }

    public function employmentStatus() {
        return $this->belongsTo(EmploymentStatus::class);
    }

    public function salaryStructure() {
        return $this->belongsTo(SalaryStructure::class);
    }

    public function isOnProbation() {
        $today = now()->toDateString();
        return $this->probation_period_start_date && $this->probation_period_end_date &&
               $today >= $this->probation_period_start_date && $today <= $this->probation_period_end_date;
    }

    public function isContractExpired() {
        $today = now()->toDateString();
        return $this->contract_expiry_date && $today > $this->contract_expiry_date;
    }

    public function documents() {
        return $this->hasMany(Document::class);
    }

}
