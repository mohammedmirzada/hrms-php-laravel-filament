<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Position extends Model {

    use HasCreatedUpdatedBy;
    use HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = [
        'name',
        'created_by',
        'updated_by',
    ];

    public function employers() {
        return $this->hasMany(Employer::class);
    }

}
