<?php

namespace App\Filament\Employee\Widgets;

use App\Enums\AttendanceEventType;
use App\Models\AttendanceEvent;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class TodayAttendanceWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $employee = Auth::guard('employer')->user();

        $events = AttendanceEvent::where('employer_id', $employee->id)
            ->whereDate('event_at', today())
            ->where('is_valid', true)
            ->orderBy('event_at')
            ->get();

        if ($events->isEmpty()) {
            return [
                Stat::make('Today', 'No attendance yet')
                    ->description('You have not clocked in today.')
                    ->color('gray'),
            ];
        }

        $firstIn = $events->firstWhere('event_type', AttendanceEventType::In->value);
        $lastOut = $events->where('event_type', AttendanceEventType::Out->value)->last();
        $lastEvent = $events->last();
        $isClockedIn = $lastEvent->event_type === AttendanceEventType::In->value;

        $stats = [];

        $stats[] = Stat::make('First In', $firstIn ? $firstIn->event_at->format('h:i A') : '—')
            ->description($isClockedIn ? 'Currently clocked in' : 'Clocked out')
            ->color($isClockedIn ? 'success' : 'gray');

        $stats[] = Stat::make('Last Out', $lastOut ? $lastOut->event_at->format('h:i A') : '—')
            ->description($lastOut ? 'Last clock out' : 'Not clocked out yet')
            ->color($lastOut ? 'info' : 'warning');

        if ($firstIn) {
            $end = $isClockedIn ? now() : ($lastOut?->event_at ?? now());
            $worked = (int) $firstIn->event_at->diffInMinutes($end);
            $hours = intdiv($worked, 60);
            $mins = $worked % 60;

            $stats[] = Stat::make('Worked', "{$hours}h {$mins}m")
                ->description($isClockedIn ? 'Still counting...' : 'Total today')
                ->color('primary');
        }

        return $stats;
    }
}
