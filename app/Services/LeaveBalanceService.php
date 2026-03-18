<?php

namespace App\Services;

use App\Models\LeaveBalances;
use App\Models\LeaveLedgerEntry;
use App\Models\LeaveRequest;

class LeaveBalanceService {

    /** 
     * Record a ledger entry and update the balance snapshot.
     */
    public function recordEntry(int $employerId, int $branchId, int $leaveTypeId, string $entryType, int $amountMinutes,
                                string $occurredOn, ?string $note = null, 
                                ?int $leaveRequestId = null): LeaveLedgerEntry {
        $entry = LeaveLedgerEntry::create([
            'employer_id' => $employerId,
            'branch_id' => $branchId,
            'leave_type_id' => $leaveTypeId,
            'leave_request_id' => $leaveRequestId,
            'entry_type' => $entryType,
            'amount_minutes' => $amountMinutes,
            'occurred_on' => $occurredOn,
            'note' => $note,
        ]);

        $this->refreshBalance($employerId, $branchId, $leaveTypeId);

        return $entry;
    }

    /**
     * Recalculate the balance snapshot from the ledger.
    */
    public function refreshBalance(int $employerId, int $branchId, int $leaveTypeId): LeaveBalances {

        $totalMinutes = LeaveLedgerEntry::where('employer_id', $employerId)
            ->where('branch_id', $branchId)
            ->where('leave_type_id', $leaveTypeId)
            ->sum('amount_minutes');

        $balance = LeaveBalances::updateOrCreate(
            [
                'employer_id' => $employerId,
                'branch_id' => $branchId,
                'leave_type_id' => $leaveTypeId,
            ],
            [
                'balance_minutes' => $totalMinutes,
                'balance_days' => round($totalMinutes / 480, 2),
                'as_of' => now(),
            ]
        );

        return $balance;
    }

    /**
     * Deduct balance when a leave request is approved.
     */
    public function deductForRequest(LeaveRequest $request): LeaveLedgerEntry
    {
        return $this->recordEntry(
            employerId: $request->employer_id,
            branchId: $request->branch_id,
            leaveTypeId: $request->leave_type_id,
            entryType: 'DEDUCTION',
            amountMinutes: -abs($request->duration_minutes),
            occurredOn: $request->start_at->toDateString(),
            note: "Deduction for leave request #{$request->id}",
            leaveRequestId: $request->id,
        );
    }

    /**
     * Reverse a deduction when a leave request is cancelled.
     */
    public function reverseForRequest(LeaveRequest $request): LeaveLedgerEntry
    {
        return $this->recordEntry(
            employerId: $request->employer_id,
            branchId: $request->branch_id,
            leaveTypeId: $request->leave_type_id,
            entryType: 'REVERSAL',
            amountMinutes: abs($request->duration_minutes),
            occurredOn: now()->toDateString(),
            note: "Reversal for cancelled leave request #{$request->id}",
            leaveRequestId: $request->id,
        );
    }
}
