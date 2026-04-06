<?php

namespace App\Filament\Employee\Widgets;

use App\Models\LeavePolicy;
use App\Services\LeaveBalanceCalculator;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class LeaveBalanceWidget extends StatsOverviewWidget {

    protected static ?int $sort = 1;

    protected function getStats(): array {
        $employer    = Auth::guard('employer')->user();
        $locale      = app()->getLocale();
        $calculator  = app(LeaveBalanceCalculator::class);

        $policies = LeavePolicy::where('branch_id', $employer->branch_id)
            ->where('accrual_enabled', true)
            ->with('leaveType')
            ->get();

        if ($policies->isEmpty()) {
            return [
                Stat::make('Leave Balance', 'No Data')
                    ->description('No leave policies with accrual configured for your branch.')
                    ->color('gray'),
            ];
        }

        return $policies->map(function (LeavePolicy $policy) use ($employer, $locale, $calculator) {
            $type   = $policy->leaveType;
            $result = $calculator->getBalance($employer, $type->id);

            $name   = $type?->getTranslation('name', $locale)
                   ?: $type?->getTranslation('name', 'en')
                   ?: 'Leave';

            $isHour    = $type?->default_unit === 'HOUR';
            $unit      = $isHour ? 'hrs' : 'days';
            $available = $isHour ? round($result['minutes'] / 60, 1) : round($result['minutes'] / 480, 1);
            $used      = $isHour ? round($result['used']    / 60, 1) : round($result['used']    / 480, 1);
            $pending   = $isHour ? round($result['pending'] / 60, 1) : round($result['pending'] / 480, 1);

            $color = match (true) {
                $available > 0  => 'success',
                $available == 0 => 'gray',
                default         => 'danger',
            };

            $availableLabel = ($available >= 0 ? '+' : '') . "{$available} {$unit} available";

            $desc = $used > 0 ? "Used: -{$used} {$unit}" : 'No leave used yet';
            if ($pending > 0) {
                $desc .= " · Pending: {$pending} {$unit}";
            }

            return Stat::make($name, $availableLabel)
                ->description($desc)
                ->color($color);
        })->toArray();
    }
}
