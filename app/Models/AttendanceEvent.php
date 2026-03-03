<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceEvent extends Model {
    
    protected array $fillable = [
        'branch_id',
        'employee_id',
        'device_id',
        'device_user_code',
        'source',
        'event_type',
        'event_at',
        'latitude',
        'longitude',
        'accuracy_m',
        'selfie_path',
        'raw_payload',
        'is_valid',
        'invalid_reason',
    ];
    
}
