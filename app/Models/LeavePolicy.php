<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;

class LeavePolicy extends Model {

    use HasCreatedUpdatedBy;
    
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
        'negative_balance_allowed',
        'negative_balance_limit',
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
        'negative_balance_allowed' => 'boolean',
        'requires_manager_approval' => 'boolean',
        'requires_hr_approval' => 'boolean',
        'requires_final_approval' => 'boolean',
        'carryover_expiry_date' => 'date',
    ];

    public function branch() {
        return $this->belongsTo(Branch::class);
    }

    public function leaveType() {
        return $this->belongsTo(LeaveType::class);
    }

    public function accuralUnits() {
        return [
            'DAY_PER_MONTH',
            'HOUR_PER_MONTH',
            'DAY_PER_YEAR',
            'HOUR_PER_YEAR',
        ];
    }

    public function accuralStartRules() {
        return [
            'HIRE_DATE',
            'AFTER_PROBATION',
            'FIXED_DATE',
        ];
    }
    
}
