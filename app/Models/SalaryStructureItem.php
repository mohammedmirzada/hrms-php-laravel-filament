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
        return match ($this->calculation_type) {
            'fixed'      => $this->value,
            'percentage' => ($baseAmount * $this->value) / 100,
            default      => throw new \InvalidArgumentException("Unknown calculation type [{$this->calculation_type}] on SalaryStructureItem ID {$this->id}."),
        };
    }

    public function types() {
        return [
            'earning' => 'Earning',
            'deduction' => 'Deduction',
        ];
    }
    
}
