<?php

namespace App\Models;

use App\Models\Concerns\HasActivityLogging;
use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EmployerShift extends Model
{
    use HasActivityLogging;
    use HasCreatedUpdatedBy;

    public function getActivitylogOptions(): \Spatie\Activitylog\Support\LogOptions
    {
        return $this->defaultLogOptions()->useLogName('employee');
    }

    protected static function booted(): void
    {
        static::created(function ($shift) {
            DB::transaction(function () use ($shift) {
                static::where('employer_id', $shift->employer_id)
                    ->whereNull('effective_to')
                    ->where('id', '!=', $shift->id)
                    ->lockForUpdate()
                    ->get();

                static::where('employer_id', $shift->employer_id)
                    ->whereNull('effective_to')
                    ->where('id', '!=', $shift->id)
                    ->update(['effective_to' => $shift->effective_from->subDay()]);
            });
        });
    }

    protected $fillable = [
        'employer_id', 'shift_id', 'effective_from', 'effective_to',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
