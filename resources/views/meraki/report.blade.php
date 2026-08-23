@extends('meraki.layout')

@section('title', 'Punch list ' . $month->format('F Y'))
@section('subtitle', 'Punch list — ' . $month->format('F Y'))

@section('content')

    <style>
        .scroll { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: .7rem 1rem; text-align: left; white-space: nowrap; }
        th {
            font-size: .8rem; text-transform: uppercase; letter-spacing: .03em;
            color: #656d76; background: #f6f8fa; border-bottom: 1px solid #d1d9e0;
        }
        tbody tr { border-top: 1px solid #eaeef2; }
        tbody tr:hover { background: #f6f8fa; }
        .time { font-variant-numeric: tabular-nums; }
        .tag {
            display: inline-block; min-width: 3.4rem; text-align: center;
            padding: .1rem .55rem; border-radius: 999px;
            font-size: .8rem; font-weight: 600; letter-spacing: .02em;
        }
        .in  { background: #dafbe1; color: #1a7f37; }
        .out { background: #ffebe9; color: #cf222e; }
        .empty { padding: 3rem 1rem; text-align: center; color: #656d76; }
    </style>

    <form method="get" class="bar">
        <label>Month
            <input type="month" name="month" value="{{ $monthKey }}">
        </label>
        <label>Person
            <select name="pin">
                <option value="">Everyone</option>
                @foreach ($people as $p => $name)
                    <option value="{{ $p }}" @selected($pin == $p)>{{ $name }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit" class="go">Show</button>
    </form>

    <div class="card">
        @if (count($rows))
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
                            <td>{{ $row['name'] }}</td>
                            <td class="time">{{ $row['date'] }} <span style="color:#8b949e">{{ $row['day'] }}</span></td>
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
            <p class="empty">No punches this month.</p>
        @endif
    </div>

    @if (count($rows))
        <p class="note">{{ count($rows) }} punches</p>
    @endif

@endsection
