<?php

namespace App\Filament\Pages\Reports;

use App\Models\AttendanceEvent;
use App\Models\Employer;
use App\Models\Holiday;
use App\Models\Shift;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
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

    private array $metricsCache = [];

    public function getSubheading(): ?string
    {
        $month     = $this->selectedMonth();
        $shiftId   = $this->tableFilters['shift_id']['value'] ?? null;
        $shiftName = $shiftId
            ? (Shift::find($shiftId)?->getTranslation('name', 'en') ?? 'Unknown shift')
            : 'All shifts';

        return $shiftName . ' • ' . $month->format('F Y') . ' (' . $month->daysInMonth . ' days)';
    }

    private function selectedMonth(): Carbon
    {
        $value = $this->tableFilters['month']['value'] ?? now()->format('Y-m');
        return Carbon::createFromFormat('Y-m', $value)->startOfMonth();
    }

    private function monthOptions(): array
    {
        $options = [];
        for ($i = 0; $i < 12; $i++) {
            $m = now()->startOfMonth()->subMonths($i);
            $options[$m->format('Y-m')] = $m->format('F Y');
        }
        return $options;
    }

    private function shiftOptions(): array
    {
        return Shift::all()
            ->mapWithKeys(fn ($s) => [$s->id => $s->getTranslation('name', 'en')])
            ->toArray();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Employer::query()->with(['department', 'branch']))
            ->columns([
                TextColumn::make('full_name')
                    ->label('Employee')
                    ->formatStateUsing(fn ($record) => $record->getTranslation('full_name', 'en'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->formatStateUsing(fn ($record) => $record->branch?->getTranslation('name', 'en'))
                    ->sortable(),
                TextColumn::make('working_days')
                    ->label('Working Days')
                    ->alignCenter()
                    ->getStateUsing(fn ($record) => $this->metrics($record)['working_days'] ?: null)
                    ->placeholder('—'),
                TextColumn::make('days_present')
                    ->label('Present')
                    ->alignCenter()
                    ->getStateUsing(fn ($record) => $this->metrics($record)['days_present'] ?: null)
                    ->placeholder('—'),
                TextColumn::make('days_absent')
                    ->label('Absent')
                    ->alignCenter()
                    ->getStateUsing(fn ($record) => $this->metrics($record)['days_absent'] ?: null)
                    ->placeholder('—')
                    ->color(fn ($state) => $state ? 'danger' : null)
                    ->weight(fn ($state) => $state ? 'bold' : null),
                TextColumn::make('total_late')
                    ->label('Late (min)')
                    ->alignCenter()
                    ->getStateUsing(fn ($record) => $this->metrics($record)['total_late'] ?: null)
                    ->placeholder('—')
                    ->color(fn ($state) => $state > 60 ? 'warning' : null),
                TextColumn::make('avg_late')
                    ->label('Avg Late (min)')
                    ->alignCenter()
                    ->getStateUsing(fn ($record) => $this->metrics($record)['avg_late'] ?: null)
                    ->placeholder('—')
                    ->color(fn ($state) => $state > 15 ? 'warning' : null),
                TextColumn::make('overtime')
                    ->label('Overtime (min)')
                    ->alignCenter()
                    ->getStateUsing(fn ($record) => $this->metrics($record)['overtime'] ?: null)
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('month')
                    ->label('Month')
                    ->options($this->monthOptions())
                    ->default(now()->format('Y-m'))
                    ->native(false)
                    ->query(fn (Builder $query) => $query),
                SelectFilter::make('shift_id')
                    ->label('Shift')
                    ->options($this->shiftOptions())
                    ->native(false)
                    ->searchable()
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn ($q, $shiftId) => $q->whereHas(
                            'employerShifts',
                            fn ($s) => $s->whereNull('effective_to')->where('shift_id', $shiftId)
                        )
                    )),
            ])
            ->striped()
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(100)
            ->defaultSort('id');
    }

    private function metrics(Employer $employer): array
    {
        return $this->metricsCache[$employer->id] ??= $this->computeMetrics($employer);
    }

    private function computeMetrics(Employer $employer): array
    {
        $month = $this->selectedMonth();
        $from  = $month->copy()->startOfMonth();
        $to    = $month->copy()->endOfMonth();

        // Stop counting at today — future days can't be "absent"
        $countUntil = $to->isFuture() ? now()->startOfDay() : $to->copy()->endOfDay();

        // Pick the shift that was active during the selected month (not just the current one)
        $shift = $employer->employerShifts()
            ->where('effective_from', '<=', $to->toDateString())
            ->where(function ($q) use ($from) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $from->toDateString());
            })
            ->latest('effective_from')
            ->with('shift')
            ->first()?->shift;

        // Working days of the week from shift (default: all 7 if no shift configured)
        $workingDow = $shift?->days_of_week ?: [1, 2, 3, 4, 5, 6, 7];

        $holidays = Holiday::where('branch_id', $employer->branch_id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->pluck('date')
            ->map(fn ($d) => $d->format('Y-m-d'))
            ->all();

        $isWorkingDate = fn (string $date) => in_array(Carbon::parse($date)->dayOfWeekIso, $workingDow)
            && ! in_array($date, $holidays);

        // Count working days = working day-of-week, not a holiday, not in the future
        $workingDays = 0;
        for ($d = $from->copy(); $d->lte($countUntil); $d->addDay()) {
            if ($isWorkingDate($d->format('Y-m-d'))) {
                $workingDays++;
            }
        }

        $eventsByDay = AttendanceEvent::where('employer_id', $employer->id)
            ->where('is_valid', true)
            ->whereBetween('event_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('event_at')
            ->get()
            ->groupBy(fn ($e) => $e->event_at->format('Y-m-d'));

        $daysPresent = 0;
        $totalLate   = 0;
        $lateDays    = 0;
        $overtime    = 0;

        foreach ($eventsByDay as $date => $dayEvents) {
            $firstIn = $dayEvents->firstWhere('event_type', 'IN');
            $lastOut = $dayEvents->where('event_type', 'OUT')->last();

            if (! $firstIn) continue;

            // Only count presence on actual working days — punches on off days don't offset absences
            if (! $isWorkingDate($date)) continue;

            $daysPresent++;

            if ($shift?->start_time) {
                $shiftStart = Carbon::parse("$date {$shift->start_time}");
                if ($firstIn->event_at->gt($shiftStart)) {
                    $totalLate += $shiftStart->diffInMinutes($firstIn->event_at);
                    $lateDays++;
                }
            }

            if ($shift?->end_time && $lastOut) {
                $shiftEnd = Carbon::parse("$date {$shift->end_time}");
                if ($lastOut->event_at->gt($shiftEnd)) {
                    $overtime += $shiftEnd->diffInMinutes($lastOut->event_at);
                }
            }
        }

        return [
            'working_days' => $workingDays,
            'days_present' => $daysPresent,
            'days_absent'  => max(0, $workingDays - $daysPresent),
            'total_late'   => (int) $totalLate,
            'avg_late'     => $lateDays > 0 ? (int) round($totalLate / $lateDays) : 0,
            'overtime'     => (int) $overtime,
        ];
    }
}
