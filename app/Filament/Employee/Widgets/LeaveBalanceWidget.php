<?php

namespace App\Filament\Employee\Widgets;

use App\Models\LeaveBalances;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class LeaveBalanceWidget extends StatsOverviewWidget {

    protected static ?int $sort = 1;

    protected function getStats(): array {
        $employer = Auth::guard('employer')->user();
        $locale   = app()->getLocale();

        $balances = LeaveBalances::with('leaveType')
            ->where('employer_id', $employer->id)
            ->get();

        if ($balances->isEmpty()) {
            return [
                Stat::make('Leave Balance', 'No Data')
                    ->description('No leave balances assigned yet.')
                    ->color('gray'),
            ];
        }

        return $balances->map(function (LeaveBalances $balance) use ($locale) {
            $type  = $balance->leaveType;
            $name  = $type?->getTranslation('name', $locale)
                  ?: $type?->getTranslation('name', 'en')
                  ?: 'Leave';

            $isHour = $type?->default_unit === 'HOUR';
            $value  = $isHour
                ? round($balance->balance_minutes / 60, 1)
                : $balance->balance_days;
            $unit   = $isHour ? 'hrs' : 'days';

            $color = match (true) {
                $value > 0  => 'success',
                $value === 0 => 'gray',
                default     => 'danger',
            };

            return Stat::make($name, $value . ' ' . $unit)
                ->description('As of ' . ($balance->as_of?->format('M d, Y') ?? 'N/A'))
                ->color($color);
        })->toArray();
    }
}
