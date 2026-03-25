<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;

class PayrollPeriod extends Model {

    use HasCreatedUpdatedBy;

    protected static function booted() {
        static::creating(function ($payrollPeriod) {
            $payrollPeriod->exchange_rate_date = $payrollPeriod->exchange_rate_date ?? $payrollPeriod->period_end;
        });
    }
    
    protected $fillable = [
        'branch_id',
        'period_start',
        'period_end',
        'processing_currency_code',
        'exchange_rate_date',
        'status',
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
        'approved_at' => 'datetime',
        'immutable' => 'boolean'
    ];

    public function branch() {
        return $this->belongsTo(Branch::class);
    }

    public function approvedByUser() {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function statuses() {
        return [
            'open',
            'calculated',
            'approved'
        ];
    }

}
