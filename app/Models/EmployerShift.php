<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployerShift extends Model
{
    use \App\Models\Concerns\HasCreatedUpdatedBy;

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
