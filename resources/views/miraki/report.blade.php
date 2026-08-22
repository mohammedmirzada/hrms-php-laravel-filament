@php
    // minutes -> "9h 05m"
    $hm = function (int $m) {
        return intdiv($m, 60) . 'h ' . str_pad($m % 60, 2, '0', STR_PAD_LEFT) . 'm';
    };
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $clientName }} Attendance — {{ $month->format('F Y') }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; padding: 2rem 1.5rem;
            font: 14px/1.5 ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            color: #1f2328; background: #f6f8fa;
        }
        .wrap { max-width: 1000px; margin: 0 auto; }
        h1 { font-size: 1.35rem; margin: 0 0 .25rem; }
        .sub { color: #656d76; margin: 0 0 1.5rem; }
        form {
            display: flex; gap: .5rem; flex-wrap: wrap; align-items: center;
            background: #fff; border: 1px solid #d1d9e0; border-radius: 8px;
            padding: .75rem; margin-bottom: 1.25rem;
        }
        select, input, button {
            font: inherit; padding: .4rem .6rem;
            border: 1px solid #d1d9e0; border-radius: 6px; background: #fff;
        }
        button { background: #1f6feb; color: #fff; border-color: #1f6feb; cursor: pointer; }
        .link { margin-left: auto; color: #1f6feb; text-decoration: none; font-size: .85rem; }
        .link:hover { text-decoration: underline; }
        .card {
            background: #fff; border: 1px solid #d1d9e0; border-radius: 8px;
            margin-bottom: 1.25rem; overflow: hidden;
        }
        .card h2 {
            font-size: .8rem; text-transform: uppercase; letter-spacing: .04em;
            color: #656d76; margin: 0; padding: .75rem 1rem;
            border-bottom: 1px solid #d1d9e0; background: #f6f8fa;
        }
        .scroll { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: .55rem 1rem; text-align: left; white-space: nowrap; }
        th { font-size: .78rem; text-transform: uppercase; letter-spacing: .03em; color: #656d76; }
        tbody tr { border-top: 1px solid #eaeef2; }
        tbody tr:hover { background: #f6f8fa; }
        .num { text-align: right; font-variant-numeric: tabular-nums; }
        .muted { color: #8b949e; }
        .flag {
            display: inline-block; padding: .05rem .4rem; border-radius: 999px;
            font-size: .72rem; background: #fff1e5; color: #9a5b00; border: 1px solid #f5d9b8;
        }
        .pairs { font-size: .78rem; color: #656d76; }
        tfoot td { border-top: 2px solid #d1d9e0; font-weight: 600; }
        .empty { padding: 2.5rem 1rem; text-align: center; color: #656d76; }
    </style>
</head>
<body>
<div class="wrap">

    <h1>{{ $clientName }} Attendance</h1>
    <p class="sub">{{ $month->format('F Y') }} — times are exactly as the device recorded them.</p>

    <form method="get">
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
        <button type="submit">Show</button>
        <a class="link" href="{{ route('client.pull-users', ['client' => $client]) }}">Refresh names from device</a>
    </form>

    @if (count($totals))
        <div class="card">
            <h2>Summary</h2>
            <div class="scroll">
                <table>
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>PIN</th>
                        <th class="num">Days</th>
                        <th class="num">Worked</th>
                        <th class="num">Missing punch</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($totals as $t)
                        <tr>
                            <td>{{ $t['name'] }}</td>
                            <td class="muted">{{ $t['pin'] }}</td>
                            <td class="num">{{ $t['days'] }}</td>
                            <td class="num">{{ $hm($t['minutes']) }}</td>
                            <td class="num">
                                @if ($t['incomplete'])
                                    <span class="flag">{{ $t['incomplete'] }} day(s)</span>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="3">Total</td>
                        <td class="num">{{ $hm($grandMins) }}</td>
                        <td></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif

    <div class="card">
        <h2>Daily detail</h2>

        @if (count($rows))
            <div class="scroll">
                <table>
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Day</th>
                        <th>First in</th>
                        <th>Last out</th>
                        <th class="num">Worked</th>
                        <th class="num">Punches</th>
                        <th>Sessions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td>{{ $row['date'] }}</td>
                            <td class="muted">{{ $row['day'] }}</td>
                            <td>{{ $row['first'] }}</td>
                            <td>
                                @if ($row['last'])
                                    {{ $row['last'] }}
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                            <td class="num">{{ $hm($row['minutes']) }}</td>
                            <td class="num">{{ $row['count'] }}</td>
                            <td class="pairs">
                                {{ implode('  ·  ', $row['pairs']) ?: '—' }}
                                @if ($row['incomplete'])
                                    <span class="flag">no punch out</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="empty">No punches recorded for this month.</p>
        @endif
    </div>

</div>
</body>
</html>
