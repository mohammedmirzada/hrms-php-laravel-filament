<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;

class EmployerCompensation extends Model {

    use HasCreatedUpdatedBy;

    protected $table = 'employer_compensation';
    
    protected $fillable = [
        'employer_id',
        'salary_structure_id',
        'currency_code',
        'basic_salary',
        'effective_from',
        'effective_to',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function employer() {
        return $this->belongsTo(Employer::class);
    }

    public function salaryStructure() {
        return $this->belongsTo(SalaryStructure::class);
    }

}
