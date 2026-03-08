<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;

class Department extends Model {

    use HasCreatedUpdatedBy;
    
    protected array $fillable = [
        'name',
        'created_by',
        'updated_by',
    ];

    protected array $casts = [
        'name' => 'array',
    ];
    
}
