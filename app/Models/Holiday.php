<?php

namespace App\Models;

use App\Models\Concerns\HasActivityLogging;
use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Holiday extends Model {

    use HasActivityLogging;
    use HasCreatedUpdatedBy;
    use HasTranslations;

    public function getActivitylogOptions(): \Spatie\Activitylog\Support\LogOptions
    {
        return $this->defaultLogOptions()->useLogName('leave');
    }
    
    protected $fillable = [
        'branch_id',
        'date',
        'name',
        'is_working_day_override',
        'created_by',
        'updated_by',
    ];

    public array $translatable = ['name'];

    protected $casts = [
        'date' => 'date',
        'is_working_day_override' => 'boolean',
    ];

    public function branch() {
        return $this->belongsTo(Branch::class);
    }

}
