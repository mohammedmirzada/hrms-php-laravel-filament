<?php

namespace App\Models;

use App\Enums\LeaveAccrualStartRule;
use App\Enums\LeaveAccrualUnit;
use App\Models\Concerns\HasActivityLogging;
use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;

class LeavePolicy extends Model {

    use HasActivityLogging;
    use HasCreatedUpdatedBy;

    public function getActivitylogOptions(): \Spatie\Activitylog\Support\LogOptions
    {
        return $this->defaultLogOptions()->useLogName('leave');
    }
    
    protected $fillable = [
        'branch_id',
        'leave_type_id',
        'accrual_enabled',
        'accrual_rate',
        'accrual_unit',
        'accrual_start_rule',
        'accrual_start_month_day',
        'annual_cap',
        'carryover_enabled',
        'carryover_cap',
        'carryover_expiry_date',
        'allow_hourly',
        'allow_half_day',
        'min_request_unit_minutes',
        'requires_manager_approval',
        'requires_hr_approval',
        'requires_final_approval',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'accrual_enabled' => 'boolean',
        'carryover_enabled' => 'boolean',
        'allow_hourly' => 'boolean',
        'allow_half_day' => 'boolean',
        'requires_manager_approval' => 'boolean',
        'requires_hr_approval' => 'boolean',
        'requires_final_approval' => 'boolean',
        // carryover_expiry_date is stored as "MM-DD" string, not a full date
        'accrual_unit' => LeaveAccrualUnit::class,
        'accrual_start_rule' => LeaveAccrualStartRule::class,
    ];

    public function branch() {
        return $this->belongsTo(Branch::class);
    }

    public function leaveType() {
        return $this->belongsTo(LeaveType::class);
    }

    public function accuralUnits() {
        return LeaveAccrualUnit::labels();
    }

    public function accuralStartRules() {
        return LeaveAccrualStartRule::labels();
    }
    
}
