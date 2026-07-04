<?php

namespace App\Models;

use App\Models\Concerns\HasActivityLogging;
use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;

/**
 * A simple leave record — the employee was on leave for X hours on a given date.
 * Replaces the old LeaveRequest/LeaveType/LeavePolicy/balance/ledger system.
 */
class Leave extends Model
{
    use HasActivityLogging;
    use HasCreatedUpdatedBy;

    public function getActivitylogOptions(): \Spatie\Activitylog\Support\LogOptions
    {
        return $this->defaultLogOptions()->useLogName('leave');
    }

    protected $fillable = [
        'employer_id',
        'date',
        'hours',
        'note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date'  => 'date',
        'hours' => 'decimal:2',
    ];

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }
}
