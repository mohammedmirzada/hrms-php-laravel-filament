<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model {

    use HasCreatedUpdatedBy;
    
    protected $fillable = [
        'name',
        'address',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'name' => 'array',
        'address' => 'array',
    ];

    public function holidays() {
        return $this->hasMany(Holiday::class);
    }
    
}
