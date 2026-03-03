<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryStructure extends Model {
    
    protected $fillable = [
        'name',
        'default_currency_code',
        'is_active',
    ];

    public function items() {
        return $this->hasMany(SalaryStructureItem::class);
    }

}
