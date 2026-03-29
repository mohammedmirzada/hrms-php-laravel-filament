<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EmployerCompensation extends Model {

    use HasCreatedUpdatedBy;

    protected static function booted(): void {
        static::created(function ($compensation) {
            DB::transaction(function () use ($compensation) {
                static::where('employer_id', $compensation->employer_id)
                    ->whereNull('effective_to')
                    ->where('id', '!=', $compensation->id)
                    ->lockForUpdate()
                    ->get();

                static::where('employer_id', $compensation->employer_id)
                    ->whereNull('effective_to')
                    ->where('id', '!=', $compensation->id)
                    ->update(['effective_to' => $compensation->effective_from->subDay()]);
            });
        });
    }

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
