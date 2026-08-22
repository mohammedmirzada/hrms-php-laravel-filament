<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A single punch from the Miraki fingerprint device.
 *
 * Raw only — no in/out, no hours. The report works that out.
 */
class Miraki extends Model {

    protected $table = 'miraki';

    protected $fillable = [
        'pin',
        'punched_at',
        'status',
        'verify',
        'device_sn',
        'raw',
    ];

    protected $casts = [
        'punched_at' => 'datetime',
    ];

    public function user() {
        return $this->belongsTo(MirakiUser::class, 'pin', 'pin');
    }

}
