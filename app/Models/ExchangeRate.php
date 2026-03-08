<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model {

    use HasCreatedUpdatedBy;
    
    protected $fillable = [
        'base_code',
        'quote_currency',
        'rate',
        'rate_date',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'rate_date' => 'date',
    ];

}
