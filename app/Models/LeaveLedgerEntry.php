<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveLedgerEntry extends Model {
    
    protected $fillable = [
        'employer_id',
        'branch_id',
        'leave_type_id',
        'leave_request_id',
        'entry_type',
        'amount_minutes',
        'occurred_on',
        'note',
        'created_by_user_id',
    ];

    protected $casts = [
        'occurred_on' => 'date',
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

    public function leaveRequest() {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function createdByUser() {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function entryTypes() {
        return [
            'ACCRUAL',
            'DEDUCTION',
            'ADJUSTMENT',
            'REVERSAL',
            'EXPIRY'
        ];
    }

}
