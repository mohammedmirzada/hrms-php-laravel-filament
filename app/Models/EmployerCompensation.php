<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployerCompensation extends Model {
    
    protected $fillable = [
        'employer_id',
        'salary_structure_id',
        'currency_code',
        'basic_salary',
        'effective_from',
        'effective_to',
    ];

    public function employer() {
        return $this->belongsTo(Employer::class);
    }

}
