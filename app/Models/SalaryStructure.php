<?php

namespace App\Models;

use App\Models\Concerns\HasActivityLogging;
use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;

class SalaryStructure extends Model {

    use HasActivityLogging;
    use HasCreatedUpdatedBy;

    public function getActivitylogOptions(): \Spatie\Activitylog\Support\LogOptions
    {
        return $this->defaultLogOptions()->useLogName('payroll');
    }
    
    protected $fillable = [
        'name',
        'default_currency_code',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function items() {
        return $this->hasMany(SalaryStructureItem::class);
    }

}
