<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployerShift extends Model
{
    use \App\Models\Concerns\HasCreatedUpdatedBy;

    protected static function booted(): void
    {
        static::created(function ($shift) {
            static::where('employer_id', $shift->employer_id)
                ->where('id', '!=', $shift->id)
                ->whereNull('effective_to')
                ->update(['effective_to' => $shift->effective_from->subDay()]);
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
