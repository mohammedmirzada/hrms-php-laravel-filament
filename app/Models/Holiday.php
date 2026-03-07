<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model {
    
    protected $fillable = [
        'branch_id',
        'date',
        'name',
        'is_working_day_override',
    ];

    protected $casts = [
        'name' => 'array',
        'date' => 'date',
        'is_working_day_override' => 'boolean',
    ];

    public function branch() {
        return $this->belongsTo(Branch::class);
    }

}
