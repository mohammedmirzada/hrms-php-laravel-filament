<?php

namespace App\Models;

use App\Models\Concerns\HasActivityLogging;
use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;

class AttendanceEvent extends Model {

    use HasActivityLogging;
    use HasCreatedUpdatedBy;

    public function getActivitylogOptions(): \Spatie\Activitylog\Support\LogOptions
    {
        return $this->defaultLogOptions()->useLogName('attendance');
    }
    
    protected $fillable = [
        'branch_id',
        'employer_id',
        'device_id',
        'device_user_code',
        'device_serial_no',
        'source',
        'event_type',
        'event_at',
        'selfie_path',
        'is_valid',
        'invalid_reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'event_at' => 'datetime',
        'raw_payload' => 'array',
        'is_valid' => 'boolean',
    ];

    public function branch() {
        return $this->belongsTo(Branch::class);
    }

    public function employer() {
        return $this->belongsTo(Employer::class);
    }

    public function device() {
        return $this->belongsTo(AttendanceDevice::class, 'device_id');
    }
    
}
