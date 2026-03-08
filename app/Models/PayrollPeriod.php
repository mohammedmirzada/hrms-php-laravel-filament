<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;

class PayrollPeriod extends Model {

    use HasCreatedUpdatedBy;
    
    protected $fillable = [
        'branch_id',
        'period_start',
        'period_end',
        'processing_currency_code',
        'exchange_rate_date',
        'status',
        'attendance_locked_by_user_id',
        'attendance_locked_at',
        'approved_by_user_id',
        'approved_at',
        'immutable',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'exchange_rate_date' => 'date',
        'attendance_locked_at' => 'datetime',
        'approved_at' => 'datetime',
        'immutable' => 'boolean'
    ];

    public function branch() {
        return $this->belongsTo(Branch::class);
    }

    public function attendanceLockedByUser() {
        return $this->belongsTo(User::class, 'attendance_locked_by_user_id');
    }

    public function approvedByUser() {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function statuses() {
        return [
            'open',
            'attendance_locked',
            'calculated',
            'approved'
        ];
    }

}
