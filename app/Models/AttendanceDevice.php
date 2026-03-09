<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;

class AttendanceDevice extends Model {

    use HasCreatedUpdatedBy;
    
    protected $fillable = [
        'branch_id',
        'vendor',
        'name',
        'ip_address',
        'port',
        'sync_mode',
        'last_sync_at',
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
