<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;

class SocialSecurityRule extends Model {

    use HasCreatedUpdatedBy;

    protected static function booted(): void {
        static::saving(function ($rule) {
            if ($rule->effective_to !== null && $rule->effective_to <= $rule->effective_from) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'effective_to' => 'Effective To date must be after Effective From date.',
                ]);
            }
        });

        static::created(function ($rule) {
            static::where('branch_id', $rule->branch_id)
                ->where('employment_type', $rule->employment_type)
                ->where('id', '!=', $rule->id)
                ->whereNull('effective_to')
                ->update(['effective_to' => today()]);
        });
    }
    
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
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'cap_enabled' => 'boolean',
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
