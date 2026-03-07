<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceDay extends Model {

    protected array $fillable = [
        'employer_id',
        'branch_id',
        'date',
        'shift_code',
        'scheduled_start_at',
        'scheduled_end_at',
        'first_in_at',
        'last_out_at',
        'worked_minutes',
        'late_minutes',
        'overtime_minutes',
        'status',
        'is_overridden',
        'override_by_user_id',
        'override_reason',
        'override_before',
        'override_after',
        'override_at'
    ];

    protected array $casts = [
        'date' => 'date',
        'scheduled_start_at' => 'datetime',
        'scheduled_end_at' => 'datetime',
        'first_in_at' => 'datetime',
        'last_out_at' => 'datetime',
        'is_overridden' => 'boolean',
        'override_before' => 'array',
        'override_after' => 'array',
        'override_at' => 'datetime'
    ];

    public function employer() {
        return $this->belongsTo(Employer::class);
    }

    public function branch() {
        return $this->belongsTo(Branch::class);
    }

    public function overrideByUser() {
        return $this->belongsTo(User::class, 'override_by_user_id');
    }

}
