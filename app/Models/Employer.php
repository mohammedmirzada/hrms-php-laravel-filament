<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Models\Concerns\HasActivityLogging;
use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Translatable\HasTranslations;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Notifications\Notifiable;

class Employer extends Authenticatable implements FilamentUser, HasAvatar, HasName {

    use HasActivityLogging;
    use HasCreatedUpdatedBy;
    use HasTranslations;
    use Notifiable;

    public function getActivitylogOptions(): \Spatie\Activitylog\Support\LogOptions
    {
        return $this->defaultLogOptions()->useLogName('employee');
    }

    public array $translatable = ['full_name'];

    protected $hidden = ['password', 'remember_token'];

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
        'password',
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

    public function isOnProbation() {
        return $this->probation_period_start_date
            && $this->probation_period_end_date
            && now()->between($this->probation_period_start_date, $this->probation_period_end_date);
    }

    public function isContractExpired() {
        return $this->contract_expiry_date && today()->isAfter($this->contract_expiry_date);
    }

    public function employerShifts() {
        return $this->hasMany(EmployerShift::class);
    }

    public function documents() {
        return $this->hasMany(Document::class);
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if ($this->avatar) {
            return '/storage/' . $this->avatar;
        }

        return null;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function roles() {
        return $this->morphToMany(Role::class, 'model', 'model_has_roles', 'model_id', 'role_id');
    }

    public function getFilamentName(): string
    {
        return $this->getTranslation('full_name', app()->getLocale())
            ?: $this->getTranslation('full_name', 'en');
    }

    public function activities()
    {
        return $this->morphMany(Activity::class, 'causer');
    }

}
