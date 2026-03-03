<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceBranchSetting extends Model {

    protected array $fillable = [
        'branch_id',
        'settings'
    ];

    protected array $casts = [
        'settings' => 'array'
    ];

}