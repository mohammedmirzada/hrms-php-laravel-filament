<?php

namespace App\Models;

use App\Enums\LeaveRequestDayPart;
use App\Enums\LeaveRequestStatus;
use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model {

    use HasCreatedUpdatedBy;

    protected static function booted(): void
    {
        static::creating(function (LeaveRequest $request) {
            $request->status ??= LeaveRequestStatus::Draft->value;
        });

        static::saving(function (LeaveRequest $request) {
            if ($request->start_at && $request->end_at) {
                $overlap = static::where('employer_id', $request->employer_id)
                    ->whereNotIn('status', ['CANCELLED', 'REJECTED'])
                    ->where('start_at', '<=', $request->end_at)
                    ->where('end_at', '>=', $request->start_at)
                    ->when($request->exists, fn ($q) => $q->where('id', '!=', $request->id))
                    ->exists();

                if ($overlap) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'start_at' => 'This employee already has a leave request overlapping this period.',
                    ]);
                }
            }

            $original = $request->getOriginal('status');
            $new = $request->status;

            if ($original === $new) {
                return;
            }

            $allowed = [
                null => [LeaveRequestStatus::Draft->value, LeaveRequestStatus::Submitted->value],
                ...LeaveRequestStatus::transitions(),
            ];

            if (array_key_exists($original, $allowed) && ! in_array($new, $allowed[$original])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'status' => "Cannot transition from " . ($original ?? 'new') . " to {$new}.",
                ]);
            }

            $now = now();

            match ($new) {
                LeaveRequestStatus::Submitted->value     => $request->submitted_at = $request->submitted_at ?? $now,
                LeaveRequestStatus::FinalApproved->value => $request->approved_at = $now,
                LeaveRequestStatus::Rejected->value      => $request->rejected_at = $now,
                LeaveRequestStatus::Cancelled->value     => $request->canceled_at = $now,
                default                                  => null,
            };
        });
    }
    
    protected $fillable = [
        'employer_id',
        'branch_id',
        'leave_type_id',
        'policy_id',
        'start_at',
        'end_at',
        'duration_minutes',
        'duration_days',
        'day_part',
        'reason',
        'attachment_path',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'day_part' => LeaveRequestDayPart::class,
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    public function employer() {
        return $this->belongsTo(Employer::class);
    }

    public function branch() {
        return $this->belongsTo(Branch::class);
    }

    public function leaveType() {
        return $this->belongsTo(LeaveType::class);
    }

    public function policy() {
        return $this->belongsTo(LeavePolicy::class);
    }

    public function approvals() {
        return $this->hasMany(LeaveRequestApproval::class);
    }

    public function statusses(): array
    {
        return LeaveRequestStatus::labels();
    }

}
