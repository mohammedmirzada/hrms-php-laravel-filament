<?php

namespace App\Observers;

use App\Enums\LeaveRequestStatus;
use App\Models\LeaveRequest;
use App\Services\LeaveBalanceService;

class LeaveRequestObserver
{
    public function __construct(
        private LeaveBalanceService $balanceService,
    ) {}

    public function updated(LeaveRequest $request): void
    {
        if (! $request->wasChanged('status')) {
            return;
        }

        $new = $request->status;
        $old = $request->getOriginal('status');

        activity('leave')
            ->performedOn($request)
            ->causedBy(auth()->user())
            ->withProperties([
                'old_status' => $old,
                'new_status' => $new,
                'employer_id' => $request->employer_id,
                'leave_type_id' => $request->leave_type_id,
                'duration_days' => $request->duration_days,
            ])
            ->log("Leave request status changed from {$old} to {$new}");

        // Deduct balance when fully approved
        if ($new === LeaveRequestStatus::FinalApproved->value && $old !== LeaveRequestStatus::FinalApproved->value) {
            $this->balanceService->deductForRequest($request);
        }

        // Reverse deduction when a previously approved request is cancelled
        if ($new === LeaveRequestStatus::Cancelled->value && $old === LeaveRequestStatus::FinalApproved->value) {
            $this->balanceService->reverseForRequest($request);
        }
    }
}
