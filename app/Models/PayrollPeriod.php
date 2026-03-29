<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedUpdatedBy;
use App\Models\ExchangeRate;
use Illuminate\Database\Eloquent\Model;

class PayrollPeriod extends Model {

    use HasCreatedUpdatedBy;

    protected static function booted() {
        static::updating(function ($payrollPeriod) {
            if ($payrollPeriod->getOriginal('immutable') === true) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'immutable' => 'This payroll period is locked and cannot be modified.',
                ]);
            }
        });

        static::creating(function ($payrollPeriod) {
            if ($payrollPeriod->processing_currency_code !== 'USD' && ! $payrollPeriod->exchange_rate_date) {
                $rateDate = ExchangeRate::where('base_code', 'USD')
                    ->where('quote_currency', $payrollPeriod->processing_currency_code)
                    ->latest('rate_date')
                    ->value('rate_date');

                if (! $rateDate) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'processing_currency_code' => 'No exchange rate found for ' . $payrollPeriod->processing_currency_code . '. Please add one in Exchange Rates before creating this payroll period.',
                    ]);
                }

                $payrollPeriod->exchange_rate_date = $rateDate;
            }
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
