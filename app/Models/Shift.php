<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Shift extends Model
{
    use HasCreatedUpdatedBy;
    use HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = [
        'branch_id', 'code', 'name', 'start_time', 'end_time', 'days_of_week',
    ];

    protected $casts = [
        'days_of_week' => 'array',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function employerShifts()
    {
        return $this->hasMany(EmployerShift::class);
    }
}
