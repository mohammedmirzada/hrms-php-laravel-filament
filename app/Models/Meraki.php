<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A single punch from the Meraki fingerprint device.
 *
 * Raw only — no in/out, no hours. The report works that out.
 */
class Meraki extends Model {

    protected $table = 'meraki';

    protected $fillable = [
        'pin',
        'punched_at',
        'status',
        'verify',
        'device_sn',
        'raw',

        // The shift this person was on when they punched. Stamped once, on
        // the way in, so moving them later cannot rewrite this day.
        'shift_id',
    ];

    protected $casts = [
        'punched_at' => 'datetime',
    ];

    public function user() {
        return $this->belongsTo(MerakiUser::class, 'pin', 'pin');
    }

}
