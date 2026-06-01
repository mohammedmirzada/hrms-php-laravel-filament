<?php

namespace App\Filament\Pages\Reports;

use App\Enums\LeaveRequestStatus;
use App\Models\AttendanceEvent;
use App\Models\Branch;
use App\Models\Employer;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Shift;
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
    public ?string $shiftId = null;
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

    public function shiftOptions(): array
    {
        return Shift::all()
            ->mapWithKeys(fn ($s) => [$s->id => $s->getTranslation('name', 'en')])
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
            ->with(['branch', 'employerShifts.shift'])
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->when($this->shiftId, fn ($q) => $q->whereHas(
                'employerShifts',
                fn ($s) => $s->whereNull('effective_to')->where('shift_id', $this->shiftId)
            ))
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

        $leaveByEmployer = LeaveRequest::whereIn('employer_id', $employerIds)
            ->where('status', LeaveRequestStatus::FinalApproved->value)
            ->where('start_at', '<=', $to->copy()->endOfDay())
            ->where('end_at', '>=', $from->copy()->startOfDay())
            ->get()
            ->groupBy('employer_id');

        $rows = [];

        foreach ($employers as $employer) {
            // Shift that was active during the selected month (same rule as AttendanceReport).
            $shift = $employer->employerShifts
                ->filter(function ($es) use ($from, $to) {
                    return $es->effective_from && $es->effective_from->lte($to)
                        && (is_null($es->effective_to) || $es->effective_to->gte($from));
                })
                ->sortByDesc('effective_from')
                ->first()?->shift;

            $workingDow = $shift?->days_of_week ?: [1, 2, 3, 4, 5, 6, 7];
            $holidays   = $holidaysByBranch[$employer->branch_id] ?? [];

            $eventsByDay = ($eventsByEmployer[$employer->id] ?? collect())
                ->groupBy(fn ($e) => $e->event_at->format('Y-m-d'));

            // Expand approved leave requests into a set of covered dates within the month.
            $leaveDates = [];
            foreach ($leaveByEmployer[$employer->id] ?? collect() as $leave) {
                $start = $leave->start_at->gt($from) ? $leave->start_at->copy()->startOfDay() : $from->copy();
                $end   = $leave->end_at->lt($to) ? $leave->end_at->copy() : $to->copy();
                for ($x = $start->copy(); $x->lte($end); $x->addDay()) {
                    $leaveDates[$x->format('Y-m-d')] = true;
                }
            }

            $cells       = [];
            $sumPresent  = 0;
            $sumAbsent   = 0;
            $sumLate     = 0;

            foreach ($days as $day) {
                $date      = $day['date'];
                $isFuture  = Carbon::parse($date)->gt($today);
                $dayEvents = $eventsByDay[$date] ?? null;
                $firstIn   = $dayEvents?->firstWhere('event_type', 'IN');
                $lastOut   = $dayEvents ? $dayEvents->where('event_type', 'OUT')->last() : null;

                $in = $out = null;
                $late = $overtime = 0;

                if ($firstIn) {
                    $status = 'present';
                    $sumPresent++;
                    $in = $firstIn->event_at->format('H:i');

                    if ($lastOut) {
                        $out = $lastOut->event_at->format('H:i');
                    }

                    if ($shift?->start_time) {
                        $shiftStart = Carbon::parse("$date {$shift->start_time}");
                        if ($firstIn->event_at->gt($shiftStart)) {
                            $late = (int) round($shiftStart->diffInMinutes($firstIn->event_at));
                            if ($late > 0) {
                                $status = 'late';
                                $sumLate += $late;
                            }
                        }
                    }

                    if ($shift?->end_time && $lastOut) {
                        $shiftEnd = Carbon::parse("$date {$shift->end_time}");
                        if ($lastOut->event_at->gt($shiftEnd)) {
                            $overtime = (int) round($shiftEnd->diffInMinutes($lastOut->event_at));
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
                    'out'      => $out,
                    'late'     => $late,
                    'overtime' => $overtime,
                ];
            }

            $rows[] = [
                'id'      => $employer->id,
                'name'    => $employer->getTranslation('full_name', 'en'),
                'branch'  => $employer->branch?->getTranslation('name', 'en'),
                'shift'   => $shift?->getTranslation('name', 'en'),
                'cells'   => $cells,
                'present' => $sumPresent,
                'absent'  => $sumAbsent,
                'late'    => $sumLate,
            ];
        }

        return ['days' => $days, 'rows' => $rows];
    }
}
