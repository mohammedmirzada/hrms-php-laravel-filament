<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;

class Position extends Model {

    use HasCreatedUpdatedBy;

    protected $fillable = [
        'name',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'name' => 'array',
    ];

}
