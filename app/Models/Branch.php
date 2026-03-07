<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model {
    
    protected $fillable = [
        'name',
        'address',
    ];

    protected $casts = [
        'name' => 'array',
        'address' => 'array',
    ];

    public function holidays() {
        return $this->hasMany(Holiday::class);
    }
    
}
