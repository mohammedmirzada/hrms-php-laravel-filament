<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmploymentStatus extends Model {
    
    protected $fillable = [
        'name',
        'code',
    ];

    protected $casts = [
        'name' => 'array',
    ];
    
}
