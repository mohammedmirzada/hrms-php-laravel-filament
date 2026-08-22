<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * pin -> name, pulled from the device.
 * Punches only carry the pin, so the report joins on this.
 */
class MirakiUser extends Model {

    protected $table = 'miraki_users';

    protected $fillable = [
        'pin',
        'name',
        'privilege',
        'device_sn',
    ];

    public function punches() {
        return $this->hasMany(Miraki::class, 'pin', 'pin');
    }

}
