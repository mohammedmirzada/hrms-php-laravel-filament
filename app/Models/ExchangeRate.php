<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model {
    
    protected $fillable = [
        'base_code',
        'quote_currency',
        'rate',
        'rate_date',
        'created_by',
    ];

    protected $casts = [
        'rate_date' => 'date',
    ];

    public function createdBy() {
        return $this->belongsTo(User::class, 'created_by');
    }

}
