<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;

// Ledger = Transaction History
class LeaveLedgerEntry extends Model {

    use HasCreatedUpdatedBy;
    
    protected $fillable = [
        'employer_id',
        'branch_id',
        'leave_type_id',
        'leave_request_id',
        'entry_type',
        'amount_minutes',
        'occurred_on',
        'note',
        'created_by',
        'updated_by',
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
