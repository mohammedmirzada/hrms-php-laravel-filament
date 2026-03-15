<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;

class LeaveRequestApproval extends Model {

    use HasCreatedUpdatedBy;

    protected static function booted(): void
    {
        static::saving(function (LeaveRequestApproval $approval) {
            $original = $approval->getOriginal('status');
            $new = $approval->status;

            if ($original === $new) {
                return;
            }

            if (in_array($new, ['APPROVED', 'REJECTED', 'SKIPPED'])) {
                $approval->action_by_user_id = auth()->id();
                $approval->action_at = now();
            }
        });
    }

    protected $fillable = [
        'leave_request_id',
        'step',
        'role',
        'assigned_to_user_id',
        'status',
        'comment',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'action_at' => 'datetime',
    ];

    public function leaveRequest() {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function assignedToUser() {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function actionByUser() {
        return $this->belongsTo(User::class, 'action_by_user_id');
    }

}
