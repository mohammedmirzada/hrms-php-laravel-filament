<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * pin -> name, pulled from the device.
 * Punches only carry the pin, so the report joins on this.
 */
class MerakiUser extends Model {

    protected $table = 'meraki_users';

    protected $fillable = [
        'pin',
        'name',
        'privilege',
        'device_sn',
    ];

    public function punches() {
        return $this->hasMany(Meraki::class, 'pin', 'pin');
    }

}
