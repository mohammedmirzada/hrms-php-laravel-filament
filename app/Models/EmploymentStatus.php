<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;

class EmploymentStatus extends Model {

    use HasCreatedUpdatedBy;
    
    protected $fillable = [
        'name',
        'code',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'name' => 'array',
    ];
    
}
