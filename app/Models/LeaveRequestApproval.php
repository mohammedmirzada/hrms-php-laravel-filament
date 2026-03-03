<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequestApproval extends Model {
    
    protected $fillable = [
        'leave_request_id',
        'step',
        'role',
        'assigned_to_user_id',
        'status',
        'action_by_user_id',
        'status',
        'action_by_user_id',
        'action_at',
        'comment'
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
