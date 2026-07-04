<?php

namespace App\Filament\Pages\Reports;

use App\Models\AttendanceEvent;
use App\Models\Branch;
use App\Models\Employer;
use App\Models\Holiday;
use App\Models\Leave;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class AttendanceCalendar extends Page
{
    use HasPageShield;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::TableCells;
    protected static string|UnitEnum|null $navigationGroup = 'Reports';
    protected static ?string $navigationLabel = 'Attendance Calendar';
    protected static ?int $navigationSort = 3;
    protected static ?string $title = 'Monthly Attendance Calendar';

    protected string $view = 'filament.pages.reports.attendance-calendar';

    public ?string $month = null;
    public ?string $branchId = null;
    public ?string $search = null;

    public function mount(): void
    {
        $this->month ??= now()->format('Y-m');
    }

    public function getSubheading(): ?string
    {
        $m = $this->selectedMonth();

        return $m->format('F Y') . ' • ' . $m->daysInMonth . ' days';
    }

    private function selectedMonth(): Carbon
    {
        $value = $this->month ?: now()->format('Y-m');

        return Carbon::createFromFormat('Y-m', $value)->startOfMonth();
    }

    public function monthOptions(): array
    {
        $options = [];
        for ($i = 0; $i < 12; $i++) {
            $m = now()->startOfMonth()->subMonths($i);
            $options[$m->format('Y-m')] = $m->format('F Y');
        }

        return $options;
    }

    public function branchOptions(): array
    {
        return Branch::all()
            ->mapWithKeys(fn ($b) => [$b->id => $b->getTranslation('name', 'en')])
            ->toArray();
    }

    /** Presentation metadata for each day status (CSS class + legend label). */
    public function statusMeta(): array
    {
        return [
            'present' => ['label' => 'Present', 'class' => 'att-present'],
            'late'    => ['label' => 'Late',    'class' => 'att-late'],
            'absent'  => ['label' => 'Absent',  'class' => 'att-absent'],
            'leave'   => ['label' => 'Leave',   'class' => 'att-leave'],
            'holiday' => ['label' => 'Holiday', 'class' => 'att-holiday'],
            'off'     => ['label' => 'Off-day', 'class' => 'att-off'],
            'future'  => ['label' => '',        'class' => 'att-future'],
        ];
    }

    /**
     * Build the full attendance matrix for the current filters.
     *
     * @return array{days: array<int, array>, rows: array<int, array>}
     */
    public function getGrid(): array
    {
        $month = $this->selectedMonth();
        $from  = $month->copy()->startOfMonth();
        $to    = $month->copy()->endOfMonth();
        $today = now()->startOfDay();

        // Calendar header: one entry per day of the month.
        $days = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $days[] = [
                'date'   => $d->format('Y-m-d'),
                'num'    => $d->day,
                'iso'    => $d->dayOfWeekIso,
                'letter' => substr($d->format('D'), 0, 1),
            ];
        }

        $employers = Employer::query()
            ->with('branch')
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->when($this->search, fn ($q) => $q->where('full_name', 'like', '%' . $this->search . '%'))
            ->orderBy('id')
            ->get();

        if ($employers->isEmpty()) {
            return ['days' => $days, 'rows' => []];
        }

        $employerIds = $employers->pluck('id')->all();
        $branchIds   = $employers->pluck('branch_id')->filter()->unique()->all();

        // One query each — grouped/expanded in memory to avoid N+1 per day.
        $eventsByEmployer = AttendanceEvent::whereIn('employer_id', $employerIds)
            ->where('is_valid', true)
            ->whereBetween('event_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('event_at')
            ->get()
            ->groupBy('employer_id');

        // Holidays are branch-level. NOTE: mirrors AttendanceReport — every Holiday
        // row in range counts as a holiday (is_working_day_override is ignored here).
        $holidaysByBranch = Holiday::whereIn('branch_id', $branchIds)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->groupBy('branch_id')
            ->map(fn (Collection $g) => $g->map(fn ($h) => $h->date->format('Y-m-d'))->all());

        $leaveByEmployer = Leave::whereIn('employer_id', $employerIds)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->groupBy('employer_id');

        $rows = [];

        foreach ($employers as $employer) {
            // Company working days are hardcoded (config/attendance.php); working
            // hours are fixed per employee.
            $workingDow = config('attendance.working_days');
            $workStart  = $employer->work_start_time;
            $workEnd    = $employer->work_end_time;
            $holidays   = $holidaysByBranch[$employer->branch_id] ?? [];

            $eventsByDay = ($eventsByEmployer[$employer->id] ?? collect())
                ->groupBy(fn ($e) => $e->event_at->format('Y-m-d'));

            // Each leave record covers a single date.
            $leaveDates = [];
            foreach ($leaveByEmployer[$employer->id] ?? collect() as $leave) {
                $leaveDates[$leave->date->format('Y-m-d')] = true;
            }

            $cells       = [];
            $sumPresent  = 0;
            $sumAbsent   = 0;
            $sumLate     = 0;
            $sumMissing  = 0;

            foreach ($days as $day) {
                $date      = $day['date'];
                $isFuture  = Carbon::parse($date)->gt($today);
                $dayEvents = $eventsByDay[$date] ?? null;
                $firstIn   = $dayEvents?->firstWhere('event_type', 'IN');
                $lastOut   = $dayEvents ? $dayEvents->where('event_type', 'OUT')->last() : null;

                $in = $out = $inShort = null;
                $late = $overtime = $missing = 0;

                if ($firstIn) {
                    $status = 'present';
                    $sumPresent++;
                    // Compact 12h for the cell (e.g. "9:24"), full 12h for the tooltip ("9:24 AM").
                    $inShort = $firstIn->event_at->format('g:i');
                    $in      = $firstIn->event_at->format('g:i A');

                    if ($lastOut) {
                        $out = $lastOut->event_at->format('g:i A');
                    }

                    if ($workStart) {
                        $shiftStart = Carbon::parse("$date {$workStart}");
                        if ($firstIn->event_at->gt($shiftStart)) {
                            $late = (int) round($shiftStart->diffInMinutes($firstIn->event_at));
                            if ($late > 0) {
                                $status = 'late';
                                $sumLate += $late;
                            }
                        }
                    }

                    if ($workEnd && $lastOut) {
                        $shiftEnd = Carbon::parse("$date {$workEnd}");
                        if ($lastOut->event_at->gt($shiftEnd)) {
                            $overtime = (int) round($shiftEnd->diffInMinutes($lastOut->event_at));
                        }
                    }

                    // Missing (lost) hours: shortfall vs the required shift end.
                    // Required checkout = max(shift end, check-in + required daily minutes),
                    // so arriving late pushes the required checkout later.
                    if ($workStart && $workEnd && $lastOut) {
                        $shiftStart  = Carbon::parse("$date {$workStart}");
                        $shiftEndReq = Carbon::parse("$date {$workEnd}");
                        $requiredMin = $shiftStart->diffInMinutes($shiftEndReq);
                        $requiredOut = $firstIn->event_at->gt($shiftStart)
                            ? $firstIn->event_at->copy()->addMinutes($requiredMin)
                            : $shiftEndReq;
                        if ($lastOut->event_at->lt($requiredOut)) {
                            $missing = (int) round($lastOut->event_at->diffInMinutes($requiredOut));
                            $sumMissing += $missing;
                        }
                    }
                } elseif ($isFuture) {
                    $status = 'future';
                } elseif (isset($leaveDates[$date])) {
                    $status = 'leave';
                } elseif (in_array($date, $holidays)) {
                    $status = 'holiday';
                } elseif (! in_array($day['iso'], $workingDow)) {
                    $status = 'off';
                } else {
                    $status = 'absent';
                    $sumAbsent++;
                }

                $cells[$date] = [
                    'status'   => $status,
                    'in'       => $in,
                    'in_short' => $inShort,
                    'out'      => $out,
                    'late'     => $late,
                    'overtime' => $overtime,
                    'missing'  => $missing,
                ];
            }

            // Monthly totals (hours) for HR — shown separately then summed.
            $leaveHours = round((float) ($leaveByEmployer[$employer->id] ?? collect())->sum('hours'), 2);
            $lateHours  = round($sumLate / 60, 2);
            $missHours  = round($sumMissing / 60, 2);

            $rows[] = [
                'id'        => $employer->id,
                'name'      => $employer->getTranslation('full_name', 'en'),
                'branch'    => $employer->branch?->getTranslation('name', 'en'),
                'cells'     => $cells,
                'present'   => $sumPresent,
                'absent'    => $sumAbsent,
                'late_h'    => $lateHours,
                'leave_h'   => $leaveHours,
                'missing_h' => $missHours,
                'total_h'   => round($lateHours + $leaveHours + $missHours, 2),
            ];
        }

        return ['days' => $days, 'rows' => $rows];
    }
}
