<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Employer extends Model {

    use HasCreatedUpdatedBy;
    use HasTranslations;

    public array $translatable = ['full_name'];

    protected $fillable = [
        'full_name',
        'profile_picture',
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
        'branch_id',
        'hire_date',
        'probation_period_start_date',
        'probation_period_end_date',
        'contract_expiry_date',
        'employment_status_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'emergency_contact' => 'array',
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'probation_period_start_date' => 'date',
        'probation_period_end_date' => 'date',
        'contract_expiry_date' => 'date',
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

    public function branch() {
        return $this->belongsTo(Branch::class);
    }

    public function subordinates() {
        return $this->hasMany(Employer::class, 'manager_id');
    }

    public function employmentStatus() {
        return $this->belongsTo(EmploymentStatus::class);
    }

    public function compensations() {
        return $this->hasMany(EmployerCompensation::class);
    }

    public function leaveRequests() {
        return $this->hasMany(LeaveRequest::class);
    }

    public function attendanceDays() {
        return $this->hasMany(AttendanceDay::class);
    }

    public function isOnProbation() {
        return $this->probation_period_start_date
            && $this->probation_period_end_date
            && now()->between($this->probation_period_start_date, $this->probation_period_end_date);
    }

    public function isContractExpired() {
        return $this->contract_expiry_date && now()->isAfter($this->contract_expiry_date);
    }

    public function employerShifts() {
        return $this->hasMany(EmployerShift::class);
    }

    public function documents() {
        return $this->hasMany(Document::class);
    }

}
