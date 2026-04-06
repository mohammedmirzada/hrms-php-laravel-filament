<?php

namespace App\Models;

use App\Models\Concerns\HasActivityLogging;
use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;

class AttendanceBranchSetting extends Model {

    use HasActivityLogging;
    use HasCreatedUpdatedBy;

    public function getActivitylogOptions(): \Spatie\Activitylog\Support\LogOptions
    {
        return $this->defaultLogOptions()->useLogName('attendance');
    }

    protected $fillable = [
        'branch_id',
        'settings',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'settings' => 'array'
    ];

    public function branch() {
        return $this->belongsTo(Branch::class);
    }

}