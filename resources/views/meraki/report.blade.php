@extends('meraki.layout')

@section('title', 'Punch list ' . $label)
@section('subtitle', 'Punch list — ' . $label)

@section('content')

    <style>
        .scroll { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: .7rem 1rem; text-align: left; white-space: nowrap; }
        th {
            font-size: .74rem; font-weight: 650; text-transform: uppercase;
            letter-spacing: .06em; color: var(--mute);
            border-bottom: 1px solid var(--line);
        }
        tbody tr { border-top: 1px solid var(--line-2); }
        tbody tr:first-child { border-top: none; }
        tbody tr:hover { background: #fbfbf9; }
        td.who { font-weight: 600; }
        .time { font-variant-numeric: tabular-nums; }
        .dim { color: var(--mute); }
        .tag {
            display: inline-block; min-width: 3.2rem; text-align: center;
            padding: .12rem .6rem; border-radius: 999px;
            font-size: .78rem; font-weight: 650; letter-spacing: .03em;
            border: 1px solid transparent;
        }
        .in  { background: #eef6f1; color: var(--ok);  border-color: #cde3d7; }
        .out { background: var(--bad-bg); color: var(--bad); border-color: #f0c8c5; }
        .empty { padding: 3rem 1rem; text-align: center; color: var(--mute); }

        .pager {
            display: flex; align-items: center; gap: .6rem; flex-wrap: wrap;
            margin-top: .9rem; font-size: .88rem; color: var(--mute);
        }
        .pager .count { margin-right: auto; }
        .pager .count b { color: var(--ink); font-weight: 650; }
        .pager a, .pager span.off {
            padding: .35rem .8rem; border-radius: 7px;
            border: 1px solid var(--line); background: var(--panel);
            text-decoration: none; color: var(--ink); font-weight: 550;
        }
        .pager a:hover { border-color: var(--ink); }
        .pager span.off { color: #c0bfb9; background: transparent; }
        .pager .at { font-variant-numeric: tabular-nums; }
    </style>

    @include('meraki.parts.filter')

    <div class="card">
        @if ($rows->total())
            <div class="scroll">
                <table>
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>In / Out</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td class="who">{{ $row['name'] }}</td>
                            <td class="time">{{ $row['date'] }} <span class="dim">{{ $row['day'] }}</span></td>
                            <td class="time">{{ $row['time'] }}</td>
                            <td>
                                <span class="tag {{ $row['direction'] === 'IN' ? 'in' : 'out' }}">
                                    {{ $row['direction'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="empty">No punches for {{ $label }}.</p>
        @endif
    </div>

    @if ($rows->total())
        <div class="pager">

            <span class="count">
                Showing <b>{{ $rows->firstItem() }}–{{ $rows->lastItem() }}</b>
                of <b>{{ number_format($rows->total()) }}</b> punches —
                every press, nothing merged.
            </span>

            @if ($rows->onFirstPage())
                <span class="off">← Newer</span>
            @else
                <a href="{{ $rows->previousPageUrl() }}">← Newer</a>
            @endif

            <span class="at">Page {{ $rows->currentPage() }} of {{ $rows->lastPage() }}</span>

            @if ($rows->hasMorePages())
                <a href="{{ $rows->nextPageUrl() }}">Older →</a>
            @else
                <span class="off">Older →</span>
            @endif

        </div>
    @endif

@endsection
