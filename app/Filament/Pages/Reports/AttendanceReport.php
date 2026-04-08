<?php

namespace App\Filament\Pages\Reports;

use App\Models\AttendanceEvent;
use App\Models\Employer;
use App\Models\Holiday;
use App\Models\EmployerShift;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AttendanceReport extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::ClipboardDocumentCheck;
    protected static string|UnitEnum|null $navigationGroup = 'Reports';
    protected static ?string $navigationLabel = 'Attendance';
    protected static ?int $navigationSort = 2;
    protected static ?string $title = 'Monthly Attendance Report';

    protected string $view = 'filament.pages.reports.report';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Employer::query()->with(['department', 'branch'])
            )
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('full_name')
                    ->label('Employee')
                    ->formatStateUsing(fn ($record) => $record->getTranslation('full_name', 'en'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('department.name')
                    ->label('Department')
                    ->formatStateUsing(fn ($record) => $record->department?->getTranslation('name', 'en'))
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->formatStateUsing(fn ($record) => $record->branch?->getTranslation('name', 'en'))
                    ->sortable(),
                TextColumn::make('days_present')
                    ->label('Days Present')
                    ->getStateUsing(fn ($record) => $this->getDaysPresent($record, $this->getFilterDateRange()))
                    ->color('success'),
                TextColumn::make('days_absent')
                    ->label('Days Absent')
                    ->getStateUsing(fn ($record) => $this->getDaysAbsent($record, $this->getFilterDateRange()))
                    ->color('danger'),
                TextColumn::make('late_count')
                    ->label('Late')
                    ->getStateUsing(fn ($record) => $this->getLateCount($record, $this->getFilterDateRange()))
                    ->color('warning'),
                TextColumn::make('early_leave_count')
                    ->label('Early Leave')
                    ->getStateUsing(fn ($record) => $this->getEarlyLeaveCount($record, $this->getFilterDateRange()))
                    ->color('warning'),
                TextColumn::make('total_hours')
                    ->label('Total Hours')
                    ->getStateUsing(fn ($record) => $this->getTotalHours($record, $this->getFilterDateRange()))
                    ->numeric(decimalPlaces: 1),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name')
                    ->native(false)
                    ->searchable()
                    ->preload(),
                SelectFilter::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name')
                    ->native(false)
                    ->searchable()
                    ->preload(),
                Filter::make('date_range')
                    ->schema([
                        DatePicker::make('date_from')
                            ->native(false)
                            ->label('From')
                            ->default(now()->startOfMonth()),
                        DatePicker::make('date_to')
                            ->native(false)
                            ->label('To')
                            ->default(now()->endOfMonth()),
                    ])
                    ->query(fn (Builder $query, array $data) => $query),
            ])
            ->defaultSort('id');
    }

    private function getFilterDateRange(): array
    {
        $data = $this->tableFilters['date_range'] ?? [];
        return [
            'from' => Carbon::parse($data['date_from'] ?? now()->startOfMonth()),
            'to' => Carbon::parse($data['date_to'] ?? now()->endOfMonth()),
        ];
    }

    private function getAttendanceEvents(Employer $employer, array $range): \Illuminate\Support\Collection
    {
        return AttendanceEvent::where('employer_id', $employer->id)
            ->where('is_valid', true)
            ->whereBetween('event_at', [$range['from']->startOfDay(), $range['to']->endOfDay()])
            ->orderBy('event_at')
            ->get();
    }

    private function getDaysPresent(Employer $employer, array $range): int
    {
        return AttendanceEvent::where('employer_id', $employer->id)
            ->where('is_valid', true)
            ->where('event_type', 'IN')
            ->whereBetween('event_at', [$range['from']->startOfDay(), $range['to']->endOfDay()])
            ->selectRaw('COUNT(DISTINCT DATE(event_at)) as days_present')
            ->value('days_present') ?? 0;
    }

    private function getWorkingDays(Employer $employer, array $range): int
    {
        $holidays = Holiday::where('branch_id', $employer->branch_id)
            ->whereBetween('date', [$range['from'], $range['to']])
            ->where('is_working_day_override', false)
            ->pluck('date')
            ->map(fn ($d) => $d->format('Y-m-d'))
            ->toArray();

        $days = 0;
        $date = $range['from']->copy();
        while ($date <= $range['to']) {
            if ($date->isWeekday() && ! in_array($date->format('Y-m-d'), $holidays)) {
                $days++;
            }
            $date->addDay();
        }

        return $days;
    }

    private function getDaysAbsent(Employer $employer, array $range): int
    {
        $workingDays = $this->getWorkingDays($employer, $range);
        $daysPresent = $this->getDaysPresent($employer, $range);
        return max(0, $workingDays - $daysPresent);
    }

    private function getLateCount(Employer $employer, array $range): int
    {
        $shift = EmployerShift::where('employer_id', $employer->id)
            ->whereNull('effective_to')
            ->with('shift')
            ->first();

        if (! $shift?->shift?->start_time) return 0;

        $shiftStart = $shift->shift->start_time;

        return AttendanceEvent::where('employer_id', $employer->id)
            ->where('is_valid', true)
            ->where('event_type', 'IN')
            ->whereBetween('event_at', [$range['from']->startOfDay(), $range['to']->endOfDay()])
            ->get()
            ->filter(fn ($event) => $event->event_at->format('H:i:s') > $shiftStart)
            ->groupBy(fn ($event) => $event->event_at->format('Y-m-d'))
            ->count();
    }

    private function getEarlyLeaveCount(Employer $employer, array $range): int
    {
        $shift = EmployerShift::where('employer_id', $employer->id)
            ->whereNull('effective_to')
            ->with('shift')
            ->first();

        if (! $shift?->shift?->end_time) return 0;

        $shiftEnd = $shift->shift->end_time;

        return AttendanceEvent::where('employer_id', $employer->id)
            ->where('is_valid', true)
            ->where('event_type', 'OUT')
            ->whereBetween('event_at', [$range['from']->startOfDay(), $range['to']->endOfDay()])
            ->get()
            ->filter(fn ($event) => $event->event_at->format('H:i:s') < $shiftEnd)
            ->groupBy(fn ($event) => $event->event_at->format('Y-m-d'))
            ->count();
    }

    private function getTotalHours(Employer $employer, array $range): float
    {
        $events = $this->getAttendanceEvents($employer, $range);
        $totalMinutes = 0;

        $grouped = $events->groupBy(fn ($e) => $e->event_at->format('Y-m-d'));

        foreach ($grouped as $dayEvents) {
            $ins = $dayEvents->where('event_type', 'IN')->sortBy('event_at');
            $outs = $dayEvents->where('event_type', 'OUT')->sortBy('event_at');

            foreach ($ins as $in) {
                $out = $outs->first(fn ($o) => $o->event_at->gt($in->event_at));
                if ($out) {
                    $totalMinutes += $in->event_at->diffInMinutes($out->event_at);
                }
            }
        }

        return round($totalMinutes / 60, 1);
    }
}
