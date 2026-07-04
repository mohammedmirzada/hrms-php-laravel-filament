<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Models\Concerns\HasActivityLogging;
use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Employer extends Model {

    use HasActivityLogging;
    use HasCreatedUpdatedBy;
    use HasTranslations;

    public function getActivitylogOptions(): \Spatie\Activitylog\Support\LogOptions
    {
        return $this->defaultLogOptions()->useLogName('employee');
    }

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
        'work_start_time',
        'work_end_time',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'emergency_contact' => 'array',
        'genre' => Gender::class,
        'marital_status' => MaritalStatus::class,
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

    public function employmentStatus() {
        return $this->belongsTo(EmploymentStatus::class);
    }

    public function leaves() {
        return $this->hasMany(Leave::class);
    }

    public function documents() {
        return $this->hasMany(Document::class);
    }

    public function activities()
    {
        return $this->morphMany(Activity::class, 'causer');
    }

}
