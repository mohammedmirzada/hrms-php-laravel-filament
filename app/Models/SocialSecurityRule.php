<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialSecurityRule extends Model {
    
    protected $fillable = [
        'branch_id',
        'employment_type',
        'employer_percent',
        'employee_percent',
        'base_rule',
        'cap_enabled',
        'cap_amount',
        'currency_code',
        'effective_from',
        'effective_to',
    ];

    public function branch() {
        return $this->belongsTo(Branch::class);
    }

    public function employmentTypes() {
        return [
            'full_time' => 'Full Time',
            'part_time' => 'Part Time',
            'contract' => 'Contract',
        ];
    }

    public function baseRules() {
        return [
            'basic_only' => 'Basic Only',
            'basic_plus_marked' => 'Basic + Marked Items',
            'gross' => 'Gross Salary',
        ];
    }
    
}
