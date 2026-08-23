@extends('meraki.layout')

@section('title', 'Settings')
@section('subtitle', 'Settings')

@section('content')

    <style>
        .cols { display: flex; gap: 1.4rem; flex-wrap: wrap; align-items: flex-start; }
        .col { flex: 1 1 34rem; min-width: 0; }

        .box {
            background: var(--panel); border: 1px solid var(--line);
            border-radius: 10px; padding: 1.3rem 1.4rem;
        }
        .box h2 { font-size: 1rem; font-weight: 650; margin: 0 0 .35rem; }
        .box p.help { color: var(--mute); font-size: .9rem; margin: 0 0 1.2rem; }

        table.grid { width: 100%; border-collapse: collapse; margin-bottom: .9rem; }
        table.grid th {
            font-size: .74rem; font-weight: 650; text-transform: uppercase;
            letter-spacing: .06em; color: var(--mute); text-align: left;
            padding: 0 .45rem .5rem; border-bottom: 1px solid var(--line);
            white-space: nowrap;
        }
        table.grid th.n { text-align: right; }
        table.grid td { padding: .4rem .45rem; border-top: 1px solid var(--line-2); }
        table.grid tr:first-child td { border-top: none; }
        table.grid input { width: 100%; }
        table.grid input[type=time] { font-variant-numeric: tabular-nums; }
        table.grid td.len {
            color: var(--mute); font-size: .85rem; text-align: right; white-space: nowrap;
        }
        table.grid td.len b { color: var(--ink); font-weight: 650; }
        table.grid td.fixed {
            color: var(--mute); font-variant-numeric: tabular-nums; white-space: nowrap;
        }

        col.name  { width: auto; }
        col.time  { width: 8.5rem; }
        col.grace { width: 6rem; }
        col.len   { width: 9rem; }

        .buttons { display: flex; gap: .7rem; align-items: center; flex-wrap: wrap; }
        button.plain {
            font: inherit; font-size: .9rem; font-weight: 550; cursor: pointer;
            padding: .46rem .9rem; border-radius: 7px;
            background: transparent; color: var(--ink);
            border: 1px dashed var(--line); transition: border-color .12s ease;
        }
        button.plain:hover { border-color: var(--ink); }

        .foot { color: var(--mute); font-size: .84rem; margin: 1rem 0 0; }
        .foot b { color: var(--ink); font-weight: 600; }

        /* who is on which shift */
        table.who { width: 100%; border-collapse: collapse; }
        table.who th {
            font-size: .74rem; font-weight: 650; text-transform: uppercase;
            letter-spacing: .06em; color: var(--mute); text-align: left;
            padding: .5rem .6rem; border-bottom: 1px solid var(--line);
        }
        table.who td { padding: .4rem .6rem; border-top: 1px solid var(--line-2); }
        table.who tr:first-child td { border-top: none; }
        table.who td.nm { font-weight: 600; }
        table.who select { width: 100%; max-width: 16rem; }
        .scroll { max-height: 26rem; overflow-y: auto; margin-bottom: 1.1rem; }
        .empty { color: var(--mute); font-size: .9rem; margin: 0 0 1.1rem; }
    </style>

    @if ($saved)
        <div class="ok">Saved.</div>
    @endif

    @if ($error)
        <div class="bad-box">{{ $error }}</div>
    @endif

    <div class="cols">

        {{-- ------------------------------------------------------- shifts --}}
        <div class="col">
            <div class="box">

                <h2>Shifts</h2>
                <p class="help">
                    One row per shift. Add as many as you like and rename any of them,
                    then press Save once. The hours of a saved shift cannot be changed —
                    see below.
                </p>

                <form method="post" action="{{ route('client.settings.shifts', ['client' => $client]) }}">
                    @csrf

                    <table class="grid">
                        <colgroup>
                            <col class="name"><col class="time"><col class="time">
                            <col class="grace"><col class="len">
                        </colgroup>

                        <thead>
                        <tr>
                            <th>Shift name</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Grace</th>
                            <th class="n">Work day</th>
                        </tr>
                        </thead>

                        <tbody id="rows">
                        {{-- Rows are numbered by position, not by shift id, so a row
                             added below can never land on an id already in use.

                             The hours of a saved shift are shown, not edited: punches
                             are stamped with the shift they were made under, so moving
                             the hours would rewrite reports that are already out. --}}
                        @foreach ($shifts as $s)
                            @php ($i = $loop->index)
                            <tr>
                                <td>
                                    <input type="hidden" name="shifts[{{ $i }}][id]" value="{{ $s['id'] }}">
                                    <input type="text" name="shifts[{{ $i }}][name]"
                                           value="{{ $s['name'] }}" maxlength="40" required>
                                </td>
                                <td class="fixed">{{ $s['start'] }}</td>
                                <td class="fixed">{{ $s['end'] }}</td>
                                <td class="fixed">{{ $s['grace'] }}</td>
                                <td class="len"><b>{{ $s['text'] }}</b></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                    <div class="buttons">
                        <button type="submit" class="go">Save shifts</button>
                        <button type="button" class="plain" id="add">+ Add a shift</button>
                    </div>

                    <p class="foot">
                        <b>Grace</b> is how many minutes short still counts as a full day, so
                        five minutes late is not held against anyone.
                        <br><b>The hours are fixed once saved.</b> Every punch is stamped with
                        the shift it was made under, so changing the hours would rewrite reports
                        that have already gone out. To change hours, add a new shift and move
                        people onto it.
                        <br><b>Shifts cannot be deleted</b> for the same reason.
                    </p>
                </form>

                {{-- The new row, kept out of the form until it is asked for. --}}
                <template id="blank">
                    <tr>
                        <td>
                            <input type="text" name="shifts[__i__][name]"
                                   placeholder="Morning shift" maxlength="40" required>
                        </td>
                        <td><input type="time" name="shifts[__i__][start]" value="08:00" required></td>
                        <td><input type="time" name="shifts[__i__][end]" value="14:00" required></td>
                        <td><input type="number" name="shifts[__i__][grace]" value="5" min="0" max="240"></td>
                        <td class="len">new</td>
                    </tr>
                </template>

                <script>
                    // Nothing here is needed to save a shift — the rows above post on
                    // their own. This only adds another empty row to type into.
                    (function () {
                        var next = {{ count($shifts) }};

                        document.getElementById('add').addEventListener('click', function () {
                            var row = document.getElementById('blank').innerHTML
                                .replace(/__i__/g, next++);

                            document.getElementById('rows').insertAdjacentHTML('beforeend', row);
                        });
                    })();
                </script>

            </div>
        </div>

        {{-- ------------------------------------------- who is on which one --}}
        <div class="col">
            <div class="box">

                <h2>Who is on which shift</h2>
                <p class="help">
                    Everyone not changed here stays on <b>{{ $shifts[$defaultId]['name'] }}</b>.
                    Names come from the device itself.
                </p>

                <form method="post" action="{{ route('client.settings.people', ['client' => $client]) }}">
                    @csrf

                    @if (count($people))
                        <div class="scroll">
                            <table class="who">
                                <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Shift</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($people as $pin => $name)
                                    <tr>
                                        <td class="nm">{{ $name }}</td>
                                        <td>
                                            <select name="people[{{ $pin }}]">
                                                @foreach ($shifts as $s)
                                                    <option value="{{ $s['id'] }}"
                                                        @selected((string) ($assigned[$pin] ?? $defaultId) === $s['id'])>
                                                        {{ $s['name'] }} · {{ $s['start'] }}–{{ $s['end'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <button type="submit" class="go">Save shifts for everyone</button>
                    @else
                        <p class="empty">
                            No names yet. The device sends them by itself — they show up here
                            once it has.
                        </p>
                    @endif

                </form>

            </div>
        </div>

    </div>

@endsection
