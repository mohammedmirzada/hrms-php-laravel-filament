<x-filament-panels::page>
    @php
        $grid = $this->getGrid();
        $days = $grid['days'];
        $rows = $grid['rows'];
        $meta = $this->statusMeta();
        $todayDate = now()->format('Y-m-d');
    @endphp

    <style>
        .att-wrap { overflow-x: auto; border: 1px solid rgb(229 231 235); border-radius: 0.75rem; }
        .dark .att-wrap { border-color: rgb(55 65 81); }
        .att-table { border-collapse: separate; border-spacing: 0; font-size: 0.8125rem; }
        .att-table th, .att-table td { padding: 0; white-space: nowrap; }

        .att-day-head { width: 3rem; text-align: center; padding: 0.6rem 0; color: rgb(107 114 128); font-weight: 600; border-bottom: 1px solid rgb(229 231 235); }
        .dark .att-day-head { color: rgb(156 163 175); border-color: rgb(55 65 81); }
        .att-day-head.att-weekend { background: rgb(249 250 251); }
        .dark .att-day-head.att-weekend { background: rgb(31 41 55); }
        .att-day-head.att-today { color: var(--primary-600, #b45309); box-shadow: inset 0 -2px 0 var(--primary-500, #f59e0b); }
        .att-dow { font-size: 0.6875rem; font-weight: 400; text-transform: uppercase; margin-top: 0.15rem; }

        .att-emp-head, .att-emp {
            position: sticky; left: 0; z-index: 2;
            background: white; min-width: 13.5rem; max-width: 13.5rem;
            padding: 0.7rem 1rem; text-align: left;
            border-right: 1px solid rgb(229 231 235); border-bottom: 1px solid rgb(229 231 235);
        }
        .dark .att-emp-head, .dark .att-emp { background: rgb(17 24 39); border-color: rgb(55 65 81); }
        .att-emp-head { font-weight: 600; color: rgb(55 65 81); z-index: 3; }
        .dark .att-emp-head { color: rgb(209 213 219); }
        .att-emp .att-sub { font-size: 0.6875rem; color: rgb(156 163 175); margin-top: 0.15rem; }

        .att-cell {
            display: flex; align-items: center; justify-content: center;
            height: 2rem; margin: 3px; border-radius: 0.5rem;
            font-size: 0.75rem; font-weight: 600; cursor: default;
            transition: transform 0.1s ease;
        }
        .att-cell:hover { transform: scale(1.08); }
        .att-present { background: #dcfce7; color: #15803d; }
        .att-late    { background: #fef3c7; color: #b45309; }
        .att-absent  { background: #fee2e2; color: #b91c1c; }
        .att-leave   { background: #ede9fe; color: #6d28d9; }
        .att-holiday { background: #dbeafe; color: #1d4ed8; }
        .att-off     { background: #f3f4f6; color: #9ca3af; }
        .att-future  { background: repeating-linear-gradient(45deg, rgba(148,163,184,0.06), rgba(148,163,184,0.06) 4px, transparent 4px, transparent 8px); border: 1px solid rgba(148,163,184,0.18); }
        .dark .att-future { background: repeating-linear-gradient(45deg, rgba(148,163,184,0.10), rgba(148,163,184,0.10) 4px, transparent 4px, transparent 8px); border-color: rgba(148,163,184,0.18); }
        .dark .att-present { background: #14532d; color: #bbf7d0; }
        .dark .att-late    { background: #78350f; color: #fde68a; }
        .dark .att-absent  { background: #7f1d1d; color: #fecaca; }
        .dark .att-leave   { background: #4c1d95; color: #ddd6fe; }
        .dark .att-holiday { background: #1e3a8a; color: #bfdbfe; }
        .dark .att-off     { background: #1f2937; color: #6b7280; }

        .att-num { text-align: center; width: 3.25rem; padding: 0 0.5rem; border-bottom: 1px solid rgb(229 231 235); border-left: 1px solid rgb(243 244 246); font-weight: 600; }
        .dark .att-num { border-color: rgb(55 65 81); border-left-color: rgb(31 41 55); }
        .att-sum-head { text-align: center; width: 3.25rem; padding: 0.6rem 0.5rem; color: rgb(107 114 128); font-weight: 700; border-bottom: 1px solid rgb(229 231 235); border-left: 1px solid rgb(243 244 246); }
        .dark .att-sum-head { color: rgb(156 163 175); border-color: rgb(55 65 81); border-left-color: rgb(31 41 55); }

        .att-legend { display: flex; flex-wrap: wrap; gap: 1rem; font-size: 0.8125rem; align-items: center; color: rgb(75 85 99); }
        .dark .att-legend { color: rgb(209 213 219); }
        .att-legend span { display: inline-flex; align-items: center; gap: 0.4rem; }
        .att-swatch { width: 0.9rem; height: 0.9rem; border-radius: 0.25rem; display: inline-block; }
        .att-legend-note { color: rgb(156 163 175); }

        /* Layout + form controls (self-contained so it never depends on the Tailwind build) */
        .att-page { display: flex; flex-direction: column; gap: 1.25rem; }
        .att-filters { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 0.75rem; }
        .att-field { display: flex; flex-direction: column; }
        .att-field.att-grow { flex: 1 1 14rem; min-width: 14rem; }
        .att-label { font-size: 0.75rem; font-weight: 500; color: rgb(107 114 128); margin-bottom: 0.3rem; }
        .att-input {
            width: 100%; padding: 0.5rem 0.75rem; font-size: 0.8125rem; line-height: 1.25rem;
            border: 1px solid rgb(209 213 219); border-radius: 0.5rem;
            background: white; color: rgb(17 24 39); min-width: 9rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        }
        .att-input:focus { outline: none; border-color: var(--primary-500, #f59e0b); box-shadow: 0 0 0 2px rgba(245,158,11,0.25); }
        .dark .att-input { background: rgb(31 41 55); border-color: rgb(55 65 81); color: rgb(229 231 235); }
        .att-emp-name { font-weight: 600; color: rgb(31 41 55); }
        .dark .att-emp-name { color: rgb(243 244 246); }
        .att-num { color: rgb(55 65 81); }
        .dark .att-num { color: rgb(229 231 235); }
        .att-empty { text-align: center; color: rgb(107 114 128); padding: 3rem 1rem; border: 1px dashed rgb(209 213 219); border-radius: 0.75rem; }
        .dark .att-empty { border-color: rgb(55 65 81); }
    </style>

    <div class="att-page">
    {{-- Filters --}}
    <div class="att-filters">
        <div class="att-field">
            <label class="att-label">Month</label>
            <select wire:model.live="month" class="att-input">
                @foreach($this->monthOptions() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="att-field">
            <label class="att-label">Branch</label>
            <select wire:model.live="branchId" class="att-input">
                <option value="">All branches</option>
                @foreach($this->branchOptions() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="att-field">
            <label class="att-label">Shift</label>
            <select wire:model.live="shiftId" class="att-input">
                <option value="">All shifts</option>
                @foreach($this->shiftOptions() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="att-field att-grow">
            <label class="att-label">Employee</label>
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search by name…" class="att-input">
        </div>
    </div>

    {{-- Legend --}}
    <div class="att-legend">
        @foreach($meta as $key => $info)
            @if($info['label'])
                <span><span class="att-swatch {{ $info['class'] }}"></span>{{ $info['label'] }}</span>
            @endif
        @endforeach
        <span class="att-legend-note">· hover a cell for in/out times</span>
    </div>

    {{-- Matrix --}}
    @if(count($rows) === 0)
        <div class="att-empty">
            No employees match the selected filters.
        </div>
    @else
        <div class="att-wrap">
            <table class="att-table">
                <thead>
                    <tr>
                        <th class="att-emp-head">Employee</th>
                        @foreach($days as $day)
                            <th class="att-day-head {{ in_array($day['iso'], [6, 7]) ? 'att-weekend' : '' }} {{ $day['date'] === $todayDate ? 'att-today' : '' }}">
                                <div>{{ $day['num'] }}</div>
                                <div class="att-dow">{{ $day['letter'] }}</div>
                            </th>
                        @endforeach
                        <th class="att-sum-head">P</th>
                        <th class="att-sum-head">A</th>
                        <th class="att-sum-head">Late</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <td class="att-emp">
                                <div class="att-emp-name">{{ $row['name'] }}</div>
                                <div class="att-sub">{{ $row['branch'] ?? '—' }}{{ $row['shift'] ? ' • ' . $row['shift'] : '' }}</div>
                            </td>
                            @foreach($days as $day)
                                @php
                                    $c = $row['cells'][$day['date']];
                                    $status = $c['status'];
                                    $m = $meta[$status];

                                    $text = match ($status) {
                                        'present', 'late' => $c['in'],
                                        'absent'  => 'A',
                                        'holiday' => 'H',
                                        'leave'   => 'L',
                                        'off'     => '·',
                                        default   => '',
                                    };

                                    if (in_array($status, ['present', 'late'])) {
                                        $tip = $day['date'] . ' · In ' . ($c['in'] ?? '—') . ' · Out ' . ($c['out'] ?? '—');
                                        if ($c['late'] > 0)     { $tip .= ' · Late ' . $c['late'] . 'm'; }
                                        if ($c['overtime'] > 0) { $tip .= ' · OT ' . $c['overtime'] . 'm'; }
                                    } else {
                                        $tip = $day['date'] . ($m['label'] ? ' · ' . $m['label'] : '');
                                    }
                                @endphp
                                <td>
                                    <div class="att-cell {{ $m['class'] }}" title="{{ $tip }}">{{ $text }}</div>
                                </td>
                            @endforeach
                            <td class="att-num">{{ $row['present'] ?: '' }}</td>
                            <td class="att-num" style="color:#b91c1c">{{ $row['absent'] ?: '' }}</td>
                            <td class="att-num">{{ $row['late'] ?: '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    </div>
</x-filament-panels::page>
