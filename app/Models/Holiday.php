<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model {

    use HasCreatedUpdatedBy;
    
    protected $fillable = [
        'branch_id',
        'date',
        'name',
        'is_working_day_override',
        'created_by',
        'updated_by',
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
