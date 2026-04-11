<?php

namespace App\Models;

use App\Models\Concerns\HasActivityLogging;
use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;

class AttendanceDevice extends Model {

    use HasActivityLogging;
    use HasCreatedUpdatedBy;

    public function getActivitylogOptions(): \Spatie\Activitylog\Support\LogOptions
    {
        return $this->defaultLogOptions()->useLogName('attendance');
    }
    
    protected $fillable = [
        'branch_id',
        'vendor',
        'name',
        'ip_address',
        'port',
        'mac_address',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'last_sync_at' => 'datetime',
    ];

    public function branch() {
        return $this->belongsTo(Branch::class);
    }

}
