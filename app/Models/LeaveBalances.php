<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;

// Balance = Current Snapshot
class LeaveBalances extends Model {

    use HasCreatedUpdatedBy;
    
    protected $fillable = [
        'employer_id',
        'branch_id',
        'leave_type_id',
        'balance_minutes',
        'balance_days',
        'as_of',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'as_of' => 'datetime',
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
