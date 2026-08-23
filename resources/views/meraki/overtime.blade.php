@extends('meraki.layout')

@section('title', 'Overtime ' . $label)
@section('subtitle', 'Overtime — ' . $label)

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
        th.n, td.n { text-align: right; font-variant-numeric: tabular-nums; }
        tbody tr { border-top: 1px solid var(--line-2); }
        tbody tr:first-child { border-top: none; }
        tbody tr:hover { background: #fbfbf9; }
        td.who { font-weight: 600; }
        td.dim { color: var(--ink); }
        td.dim .len { color: var(--mute); font-size: .85rem; }
        td.extra { color: var(--extra); font-weight: 600; }
        td.short { color: var(--bad); font-weight: 600; }
        td.zero { color: #b9b8b2; font-weight: 400; }

        tfoot td {
            border-top: 1px solid var(--line);
            font-weight: 650; background: #fbfbf9;
        }

        .empty { padding: 3rem 1rem; text-align: center; color: var(--mute); }

        /* One note per line — side by side they wrapped mid sentence. */
        .legend {
            background: var(--panel); border: 1px solid var(--line);
            border-radius: 10px; padding: 1rem 1.15rem; margin-top: 1.1rem;
            font-size: .88rem; color: var(--mute);
        }
        .legend p { display: block; margin: 0 0 .5rem; }
        .legend p:last-child { margin-bottom: 0; }
        .legend p.gap { margin-top: .85rem; }
        .legend b { color: var(--ink); font-weight: 600; }
    </style>

    @include('meraki.parts.filter')

    <div class="card">
        @if (count($rows))
            <div class="scroll">
                <table>
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Shift</th>
                        <th class="n">Days came</th>
                        <th class="n">Worked</th>
                        <th class="n">Extra time</th>
                        <th class="n">Short time</th>
                        <th class="n">Days to check</th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td class="who">{{ $row['name'] }}</td>
                            <td class="dim">{{ $row['shiftName'] }} <span class="len">{{ $row['shiftText'] }}</span></td>
                            <td class="n">{{ $row['days'] }}</td>
                            <td class="n">{{ $row['workedText'] }}</td>
                            <td class="n {{ $row['extra'] > 0 ? 'extra' : 'zero' }}">
                                {{ $row['extra'] > 0 ? $row['extraText'] : '—' }}
                            </td>
                            <td class="n {{ $row['short'] > 0 ? 'short' : 'zero' }}">
                                {{ $row['short'] > 0 ? $row['shortText'] : '—' }}
                            </td>
                            <td class="n {{ $row['problems'] > 0 ? 'short' : 'zero' }}">
                                {{ $row['problems'] > 0 ? $row['problems'] : '—' }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>

                    <tfoot>
                    <tr>
                        <td>Everyone</td>
                        <td></td>
                        <td class="n"></td>
                        <td class="n">{{ $totalWorked }}</td>
                        <td class="n">{{ $totalExtra }}</td>
                        <td class="n">{{ $totalShort }}</td>
                        <td class="n"></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <p class="empty">Nobody punched in {{ $label }}.</p>
        @endif
    </div>

    <div class="legend">
        <p><b>Extra time</b> is the hours worked over a person's own work day, added up.</p>
        <p><b>Short time</b> is the hours missing under it, counted only on days they came.</p>
        <p><b>Days to check</b> is how many days have a punch missing, so those hours are a
            guess. Open the Calendar to see which days — they are the red names.</p>
        <p><b>Days off are not counted.</b> A date that nobody at all punched on is treated as
            closed — no short time for anyone. They are the greyed boxes on the Calendar.</p>

        @foreach ($shifts as $s)
            <p @class(['gap' => $loop->first])>
                <b>{{ $s['name'] }}</b> — {{ $s['start'] }} to {{ $s['end'] }}, {{ $s['text'] }}.
                @if ($s['grace'] > 0)
                    Up to {{ $s['grace'] }} min short still counts as a full day.
                @endif
            </p>
        @endforeach

        <p>Change the shifts, and who is on which one, in <b>Settings</b>.</p>
    </div>

@endsection
