<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveBalances extends Model {
    
    protected $fillable = [
        'employer_id',
        'branch_id',
        'leave_type_id',
        'balance_minutes',
        'balance_days',
        'as_of',
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
    
}
