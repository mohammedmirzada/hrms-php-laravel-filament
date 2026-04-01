<?php

namespace App\Models;

use App\Enums\EmploymentType;
use App\Enums\SocialSecurityBaseRule;
use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
            DB::transaction(function () use ($rule) {
                static::where('branch_id', $rule->branch_id)
                    ->where('employment_type', $rule->employment_type)
                    ->whereNull('effective_to')
                    ->where('id', '!=', $rule->id)
                    ->lockForUpdate()
                    ->get();

                static::where('branch_id', $rule->branch_id)
                    ->where('employment_type', $rule->employment_type)
                    ->whereNull('effective_to')
                    ->where('id', '!=', $rule->id)
                    ->update(['effective_to' => $rule->effective_from->subDay()]);
            });
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
        'employment_type' => EmploymentType::class,
        'base_rule' => SocialSecurityBaseRule::class,
        'effective_from' => 'date',
        'effective_to' => 'date',
        'cap_enabled' => 'boolean',
    ];

    public function branch() {
        return $this->belongsTo(Branch::class);
    }

    public function employmentTypes() {
        return EmploymentType::labels();
    }

    public function baseRules() {
        return SocialSecurityBaseRule::labels();
    }
    
}
