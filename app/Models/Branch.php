<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Branch extends Model {

    use HasCreatedUpdatedBy;
    use HasTranslations;

    public array $translatable = ['name', 'address'];

    protected $fillable = [
        'name',
        'address',
        'created_by',
        'updated_by',
    ];

    public function holidays() {
        return $this->hasMany(Holiday::class);
    }

    public function employers() {
        return $this->hasMany(Employer::class);
    }

}
