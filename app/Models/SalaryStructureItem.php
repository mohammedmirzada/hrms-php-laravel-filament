<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class SalaryStructureItem extends Model {

    use HasCreatedUpdatedBy;
    use HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = [
        'salary_structure_id',
        'name',
        'type',
        'calculation_type',
        'value',
        'created_by',
        'updated_by',
    ];

    protected $casts = [];

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
