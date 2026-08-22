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
            font: 15px/1.6 ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            color: #1f2328; background: #f6f8fa;
        }
        .wrap { max-width: 780px; margin: 0 auto; }
        h1 { font-size: 1.4rem; margin: 0 0 .25rem; }
        .sub { color: #656d76; margin: 0 0 1.5rem; }
        form {
            display: flex; gap: .6rem; flex-wrap: wrap; align-items: center;
            background: #fff; border: 1px solid #d1d9e0; border-radius: 8px;
            padding: .8rem; margin-bottom: 1.25rem;
        }
        label { display: flex; gap: .4rem; align-items: center; color: #656d76; font-size: .9rem; }
        select, input, button {
            font: inherit; padding: .45rem .6rem;
            border: 1px solid #d1d9e0; border-radius: 6px; background: #fff; color: #1f2328;
        }
        button { background: #1f6feb; color: #fff; border-color: #1f6feb; cursor: pointer; }
        .link { margin-left: auto; color: #1f6feb; text-decoration: none; font-size: .85rem; }
        .link:hover { text-decoration: underline; }
        .card {
            background: #fff; border: 1px solid #d1d9e0;
            border-radius: 8px; overflow: hidden;
        }
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
        .count { color: #656d76; font-size: .85rem; margin: .75rem 0 0; }
    </style>
</head>
<body>
<div class="wrap">

    <h1>{{ $clientName }} Attendance</h1>
    <p class="sub">{{ $month->format('F Y') }}</p>

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
        <a class="link" href="{{ route('client.pull-users', ['client' => $client]) }}">Refresh names</a>
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
        <p class="count">{{ count($rows) }} punches</p>
    @endif

</div>
</body>
</html>
