<?php

namespace App\Models;

use App\Models\Concerns\HasActivityLogging;
use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Department extends Model {

    use HasActivityLogging;
    use HasCreatedUpdatedBy;
    use HasTranslations;

    public array $translatable = ['name'];

    public function getActivitylogOptions(): \Spatie\Activitylog\Support\LogOptions
    {
        return $this->defaultLogOptions()->useLogName('organization');
    }

    protected $fillable = [
        'name',
        'created_by',
        'updated_by',
    ];

    public function employers() {
        return $this->hasMany(Employer::class);
    }

}
