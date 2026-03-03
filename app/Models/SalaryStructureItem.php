<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryStructureItem extends Model {
    
    protected $fillable = [
        'salary_structure_id',
        'name',
        'type',
        'calculation_type',
        'value',
    ];

    protected $casts = [
        'name' => 'array',
    ];

    public function salaryStructure() {
        return $this->belongsTo(SalaryStructure::class);
    }

    public function calculateAmount($baseAmount = 0) {
        switch ($this->calculation_type) {
            case 'fixed':
                return $this->value;
            case 'percentage':
                return ($baseAmount * $this->value) / 100;
            case 'manual':
                return $this->value; // This should be set manually for each payroll period
            default:
                return 0;
        }
    }

    public function types() {
        return [
            'earning' => 'Earning',
            'deduction' => 'Deduction',
        ];
    }
    
}
