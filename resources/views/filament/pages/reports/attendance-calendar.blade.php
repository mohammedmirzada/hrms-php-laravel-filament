<x-filament-panels::page>
    @php
        $grid = $this->getGrid();
        $days = $grid['days'];
        $rows = $grid['rows'];
        $meta = $this->statusMeta();
        $todayDate = now()->format('Y-m-d');
        $colspan = count($days) + 2; // Employee + day columns + Total
    @endphp

    <style>
        .att-wrap { overflow-x: auto; border: 1px solid rgb(229 231 235); border-radius: 0.75rem; }
        .dark .att-wrap { border-color: rgb(55 65 81); }
        .att-table { border-collapse: separate; border-spacing: 0; font-size: 0.8125rem; }
        .att-table th, .att-table td { padding: 0; white-space: nowrap; }

        .att-day-head { width: 2.7rem; min-width: 2.7rem; text-align: center; padding: 0.6rem 0; color: rgb(107 114 128); font-weight: 600; border-bottom: 1px solid rgb(229 231 235); }
        .att-day { width: 2.7rem; min-width: 2.7rem; }
        .dark .att-day-head { color: rgb(156 163 175); border-color: rgb(55 65 81); }
        .att-day-head.att-weekend { background: rgb(249 250 251); }
        .dark .att-day-head.att-weekend { background: rgb(31 41 55); }
        .att-day-head.att-today { color: var(--primary-600, #b45309); box-shadow: inset 0 -2px 0 var(--primary-500, #f59e0b); }
        .att-dow { font-size: 0.6875rem; font-weight: 400; text-transform: uppercase; margin-top: 0.15rem; }

        .att-emp-head, .att-emp {
            position: sticky; left: 0; z-index: 2; overflow: hidden;
            background: white; min-width: 12.5rem; max-width: 12.5rem; width: 12.5rem;
            padding: 0.5rem 0.85rem; text-align: left;
            border-right: 1px solid rgb(226 232 240); border-bottom: 1px solid rgb(229 231 235);
        }
        .dark .att-emp-head, .dark .att-emp { background: rgb(17 24 39); border-color: rgb(55 65 81); }
        .att-emp-head { font-weight: 600; color: rgb(55 65 81); z-index: 3; }
        .dark .att-emp-head { color: rgb(209 213 219); }
        .att-emp .att-sub { font-size: 0.6875rem; color: rgb(156 163 175); margin-top: 0.15rem; }

        .att-cell {
            display: flex; align-items: center; justify-content: center;
            width: 2.3rem; height: 1.5rem; margin: 2px auto; border-radius: 0.3rem;
            font-size: 0.64rem; font-weight: 600; cursor: default;
            transition: transform 0.1s ease;
        }
        .att-cell:hover { transform: scale(1.18); }
        .att-present { background: #e3f4e9; color: #178040; }
        .att-late    { background: #fbeeca; color: #b45309; }
        .att-absent  { background: #f8efee; color: #cf8781; }
        .att-leave   { background: #ede9fe; color: #6d28d9; }
        .att-holiday { background: #dbeafe; color: #1d4ed8; }
        .att-off     { background: #f3f4f6; color: #9ca3af; }
        .att-future  { background: repeating-linear-gradient(45deg, rgba(148,163,184,0.06), rgba(148,163,184,0.06) 4px, transparent 4px, transparent 8px); border: 1px solid rgba(148,163,184,0.18); }
        .dark .att-future { background: repeating-linear-gradient(45deg, rgba(148,163,184,0.10), rgba(148,163,184,0.10) 4px, transparent 4px, transparent 8px); border-color: rgba(148,163,184,0.18); }
        .dark .att-present { background: #14532d; color: #bbf7d0; }
        .dark .att-late    { background: #78350f; color: #fde68a; }
        .dark .att-absent  { background: rgba(127,29,29,0.3); color: #c99a95; }
        .dark .att-leave   { background: #4c1d95; color: #ddd6fe; }
        .dark .att-holiday { background: #1e3a8a; color: #bfdbfe; }
        .dark .att-off     { background: #1f2937; color: #6b7280; }

        /* Monthly totals — pinned to the right so they stay visible while day columns scroll */
        .att-num, .att-sum-head {
            position: sticky; z-index: 2; width: 2.7rem; min-width: 2.7rem; text-align: center;
            background: white; border-bottom: 1px solid rgb(229 231 235);
        }
        .dark .att-num, .dark .att-sum-head { background: rgb(17 24 39); border-color: rgb(55 65 81); }
        .att-num { padding: 0 0.3rem; font-weight: 600; }
        .att-sum-head { padding: 0.55rem 0.3rem; font-weight: 700; font-size: 0.7rem; color: rgb(107 114 128); z-index: 3; }
        .dark .att-sum-head { color: rgb(156 163 175); }
        .att-sfix { right: 0; width: 3.9rem; min-width: 3.9rem; border-left: 2px solid rgb(226 232 240); }
        .dark .att-sfix { border-left-color: rgb(51 65 85); }
        .att-total { font-weight: 800; font-size: 0.85rem; }

        /* expand arrow + per-agent detail row */
        .att-emp-row { display: flex; align-items: center; cursor: pointer; padding: 0.2rem 0; margin: -0.2rem 0; user-select: none; }
        .att-arrow { flex: 0 0 auto; width: 1.3rem; text-align: left; color: rgb(148 163 184); font-size: 0.8rem; line-height: 1; }
        .att-emp-row:hover .att-arrow { color: rgb(100 116 139); }
        .att-emp-row:hover .att-emp-name { text-decoration: underline; }
        .att-detail-row td { padding: 0; border-bottom: 1px solid rgb(229 231 235); background: rgb(248 250 252); }
        .dark .att-detail-row td { border-color: rgb(55 65 81); background: rgb(15 23 42); }
        .att-detail {
            position: sticky; left: 0; display: inline-flex; flex-wrap: wrap; gap: 0.35rem 1.2rem;
            padding: 0.55rem 0.85rem 0.55rem 2.15rem; font-size: 0.78rem; color: rgb(71 85 105);
        }
        .dark .att-detail { color: rgb(148 163 184); }
        .att-detail b { color: rgb(30 41 59); font-weight: 700; }
        .dark .att-detail b { color: rgb(226 232 240); }
        .att-detail .att-chip-p b { color: #15803d; }
        .att-detail .att-chip-a b { color: #b91c1c; }

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
        .att-emp-name { flex: 1 1 auto; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 600; color: rgb(31 41 55); }
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
        <span class="att-legend-note">· hover a cell for in/out times · click ▸ for each agent's breakdown · Missing = total missing hours this month</span>
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
                            <th class="att-day-head {{ $day['iso'] === 5 ? 'att-weekend' : '' }} {{ $day['date'] === $todayDate ? 'att-today' : '' }}">
                                <div>{{ $day['num'] }}</div>
                                <div class="att-dow">{{ $day['letter'] }}</div>
                            </th>
                        @endforeach
                        <th class="att-sum-head att-sfix">Missing</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <td class="att-emp">
                                <div class="att-emp-row" data-row="{{ $row['id'] }}" role="button" tabindex="0">
                                    <span class="att-arrow">►</span>
                                    <span class="att-emp-name" title="{{ $row['name'] }}">{{ $row['name'] }}</span>
                                </div>
                                <div class="att-sub" style="padding-left:1.5rem;">{{ $row['branch'] ?? '—' }}</div>
                            </td>
                            @foreach($days as $day)
                                @php
                                    $c = $row['cells'][$day['date']];
                                    $status = $c['status'];
                                    $m = $meta[$status];

                                    $text = match ($status) {
                                        'present', 'late' => $c['in_short'],
                                        'absent'  => 'A',
                                        'holiday' => 'H',
                                        'leave'   => 'L',
                                        'off'     => '·',
                                        default   => '',
                                    };

                                    if (in_array($status, ['present', 'late'])) {
                                        $tip = $day['date'] . ' · In ' . ($c['in'] ?? '—') . ' · Out ' . ($c['out'] ?? '—');
                                        if ($c['late'] > 0)               { $tip .= ' · Late ' . $c['late'] . 'm'; }
                                        if (($c['missing'] ?? 0) > 0)     { $tip .= ' · Missing ' . $c['missing'] . 'm'; }
                                        if ($c['overtime'] > 0)           { $tip .= ' · OT ' . $c['overtime'] . 'm'; }
                                    } else {
                                        $tip = $day['date'] . ($m['label'] ? ' · ' . $m['label'] : '');
                                    }
                                @endphp
                                <td class="att-day">
                                    <div class="att-cell {{ $m['class'] }}" data-tip="{{ $tip }}">{{ $text }}</div>
                                </td>
                            @endforeach
                            <td class="att-num att-sfix att-total">{{ $row['missing_h'] }}</td>
                        </tr>
                        <tr class="att-detail-row" id="det-{{ $row['id'] }}" style="display:none">
                            <td colspan="{{ $colspan }}">
                                <div class="att-detail">
                                    <span class="att-chip-p">Present <b>{{ $row['present'] }}</b> days</span>
                                    <span class="att-chip-a">Absent <b>{{ $row['absent'] }}</b> days</span>
                                    <span>Late <b>{{ $row['late_h'] }}</b> h</span>
                                    <span>Leave <b>{{ $row['leave_h'] }}</b> h</span>
                                    <span>Missing <b>{{ $row['missing_h'] }}</b> h</span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    </div>

    {{-- Custom hover tooltip (mounted on <body> so the scroll container can't clip it) --}}
    <script>
    (function () {
        if (window.__attTipInit) return;
        window.__attTipInit = true;

        function tipEl() {
            var t = document.getElementById('att-tip');
            if (!t) {
                t = document.createElement('div');
                t.id = 'att-tip';
                t.style.cssText = 'position:fixed;z-index:9999;pointer-events:none;display:none;'
                    + 'background:#111827;color:#f9fafb;font-size:12px;line-height:1.3;'
                    + 'padding:6px 9px;border-radius:6px;box-shadow:0 4px 14px rgba(0,0,0,.35);'
                    + 'white-space:nowrap;';
                document.body.appendChild(t);
            }
            return t;
        }

        document.addEventListener('mouseover', function (e) {
            var cell = e.target.closest && e.target.closest('.att-cell[data-tip]');
            if (!cell) return;
            var t = tipEl();
            t.textContent = cell.getAttribute('data-tip');
            t.style.display = 'block';
        });

        document.addEventListener('mousemove', function (e) {
            var t = document.getElementById('att-tip');
            if (!t || t.style.display === 'none') return;
            var r = t.getBoundingClientRect();
            var x = e.clientX + 14, y = e.clientY + 16;
            if (x + r.width > window.innerWidth)   x = e.clientX - r.width - 12;
            if (y + r.height > window.innerHeight)  y = e.clientY - r.height - 12;
            t.style.left = x + 'px';
            t.style.top = y + 'px';
        });

        document.addEventListener('mouseout', function (e) {
            var cell = e.target.closest && e.target.closest('.att-cell[data-tip]');
            if (!cell) return;
            var t = document.getElementById('att-tip');
            if (t) t.style.display = 'none';
        });

        // Expand / collapse each agent's breakdown row (click anywhere on the name).
        document.addEventListener('click', function (e) {
            var head = e.target.closest && e.target.closest('.att-emp-row');
            if (!head) return;
            var row = document.getElementById('det-' + head.getAttribute('data-row'));
            if (!row) return;
            var open = row.style.display !== 'none';
            row.style.display = open ? 'none' : '';
            var arrow = head.querySelector('.att-arrow');
            if (arrow) arrow.textContent = open ? '►' : '▼';
        });
    })();
    </script>
</x-filament-panels::page>
