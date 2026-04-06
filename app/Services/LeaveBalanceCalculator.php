<?php

namespace App\Services;

use App\Enums\LeaveAccrualStartRule;
use App\Enums\LeaveAccrualUnit;
use App\Enums\LeaveRequestStatus;
use App\Models\Employer;
use App\Models\LeavePolicy;
use App\Models\LeaveRequest;
use Carbon\Carbon;

class LeaveBalanceCalculator
{
    /**
     * Compute the leave balance for an employee and leave type.
     *
     * Returns:
     *   - minutes:  net available (accrued - used)
     *   - days:     same in days
     *   - accrued:  total accrued minutes for current period
     *   - used:     minutes from FinalApproved requests in current period
     *   - pending:  minutes from in-flight requests (not yet deducted)
     */
    public function getBalance(Employer $employee, int $leaveTypeId): array
    {
        $zero = ['minutes' => 0, 'days' => 0.0, 'accrued' => 0, 'used' => 0, 'pending' => 0];

        $policy = LeavePolicy::where('branch_id', $employee->branch_id)
            ->where('leave_type_id', $leaveTypeId)
            ->first();

        if (! $policy || ! $policy->accrual_enabled) {
            return $zero;
        }

        $periodStart = $this->resolvePeriodStart($employee, $policy);

        if (! $periodStart) {
            return $zero;
        }

        $accrued = $this->computeAccrued($policy, $periodStart);

        $used = (int) LeaveRequest::where('employer_id', $employee->id)
            ->where('leave_type_id', $leaveTypeId)
            ->where('status', LeaveRequestStatus::FinalApproved->value)
            ->where('start_at', '>=', $periodStart)
            ->sum('duration_minutes');

        $pending = (int) LeaveRequest::where('employer_id', $employee->id)
            ->where('leave_type_id', $leaveTypeId)
            ->whereIn('status', [
                LeaveRequestStatus::Submitted->value,
                LeaveRequestStatus::ManagerApproved->value,
                LeaveRequestStatus::HrApproved->value,
            ])
            ->sum('duration_minutes');

        $available = $accrued - $used;

        return [
            'minutes' => $available,
            'days'    => round($available / 480, 2),
            'accrued' => $accrued,
            'used'    => $used,
            'pending' => $pending,
        ];
    }

    /**
     * Determine the start of the current accrual period.
     */
    private function resolvePeriodStart(Employer $employee, LeavePolicy $policy): ?Carbon
    {
        $today = Carbon::today();

        return match ($policy->accrual_start_rule) {
            LeaveAccrualStartRule::HireDate => $employee->hire_date
                ? $this->currentAnniversary(Carbon::parse($employee->hire_date), $today)
                : null,

            LeaveAccrualStartRule::AfterProbation => $employee->probation_period_end_date
                ? Carbon::parse($employee->probation_period_end_date)
                : null,

            LeaveAccrualStartRule::FixedDate => $policy->accrual_start_month_day
                ? $this->resolveFixedDate($policy->accrual_start_month_day, $today)
                : null,

            default => null,
        };
    }

    /**
     * Given a hire date, return the most recent anniversary on or before today.
     */
    private function currentAnniversary(Carbon $hireDate, Carbon $today): Carbon
    {
        $anniversary = $hireDate->copy()->year($today->year);

        if ($anniversary->isAfter($today)) {
            $anniversary->subYear();
        }

        return $anniversary;
    }

    /**
     * Parse MM-DD into a Carbon date for the current (or previous) year.
     */
    private function resolveFixedDate(string $monthDay, Carbon $today): Carbon
    {
        [$month, $day] = explode('-', $monthDay);
        $date = Carbon::createFromDate($today->year, (int) $month, (int) $day);

        if ($date->isAfter($today)) {
            $date->subYear();
        }

        return $date;
    }

    /**
     * Compute how many minutes the employee has accrued in the current period.
     */
    private function computeAccrued(LeavePolicy $policy, Carbon $periodStart): int
    {
        $today = Carbon::today();
        $rate  = (float) $policy->accrual_rate;

        if (! $rate || $periodStart->isAfter($today)) {
            return 0;
        }

        $minutes = match ($policy->accrual_unit) {
            LeaveAccrualUnit::DayPerYear   => (int) round($rate * 480),
            LeaveAccrualUnit::HourPerYear  => (int) round($rate * 60),
            LeaveAccrualUnit::DayPerMonth  => (int) round($rate * 480 * min(12, (int) $periodStart->diffInMonths($today))),
            LeaveAccrualUnit::HourPerMonth => (int) round($rate * 60  * min(12, (int) $periodStart->diffInMonths($today))),
            default                        => 0,
        };

        return $this->applyAnnualCap($minutes, $policy);
    }

    /**
     * Cap accrued minutes by the policy's annual_cap (if set).
     */
    private function applyAnnualCap(int $minutes, LeavePolicy $policy): int
    {
        if ($policy->annual_cap === null) {
            return $minutes;
        }

        return min($minutes, (int) round($policy->annual_cap * 480));
    }
}
