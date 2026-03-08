<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model {

    use HasCreatedUpdatedBy;
    
    protected $fillable = [
        'employer_id',
        'branch_id',
        'leave_type_id',
        'policy_id',
        'start_at',
        'end_at',
        'duration_minutes',
        'duration_days',
        'day_part',
        'reason',
        'attachment_path',
        'status',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'canceled_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    public function employer() {
        return $this->belongsTo(Employer::class);
    }

    public function branch() {
        return $this->belongsTo(Branch::class);
    }

    public function leaveType() {
        return $this->belongsTo(LeaveType::class);
    }

    public function policy() {
        return $this->belongsTo(LeavePolicy::class);
    }

    public function statusses() {
        return [
            'DRAFT' => 'Draft',
            'SUBMITTED' => 'Submitted',
            'MANAGER_APPROVED' => 'Manager Approved',
            'HR_APPROVED' => 'HR Approved',
            'FINAL_APPROVED' => 'Final Approved',
            'REJECTED' => 'Rejected',
            'CANCELLED' => 'Cancelled'
        ];
    }

}
