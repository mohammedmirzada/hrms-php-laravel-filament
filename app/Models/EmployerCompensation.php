<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployerCompensation extends Model {

    protected $table = 'employer_compensation';
    
    protected $fillable = [
        'employer_id',
        'salary_structure_id',
        'currency_code',
        'basic_salary',
        'effective_from',
        'effective_to',
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
