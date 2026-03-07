<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceDevice extends Model {
    
    protected array $fillable = [
        'branch_id',
        'vendor',
        'name',
        'ip_address',
        'port',
        'sync_mode',
        'last_sync_at'
    ];

    protected array $casts = [
        'last_sync_at' => 'datetime',
    ];

    public function branch() {
        return $this->belongsTo(Branch::class);
    }

}
