@extends('meraki.layout')

@section('title', 'Attendance ' . $month->format('F Y'))
@section('subtitle', $month->format('F Y'))

@php
    // How many people a day box shows before the rest fold away.
    $show = 6;
@endphp

@section('content')

    <style>
        .scroll { overflow-x: auto; }
        .cal { display: grid; grid-template-columns: repeat(7, minmax(200px, 1fr)); min-width: 1400px; }
        .head {
            padding: .55rem .7rem; font-size: .74rem; font-weight: 650;
            text-transform: uppercase; letter-spacing: .06em; color: var(--mute);
            border-bottom: 1px solid var(--line);
        }
        .day {
            border-right: 1px solid var(--line-2); border-bottom: 1px solid var(--line-2);
            padding: .5rem .6rem; min-height: 94px;
        }
        .day:nth-child(7n) { border-right: none; }

        /* not part of this month at all */
        .day.pad { background: #fafaf8; }
        .day.pad .num { color: #c6c5bf; }

        /* in the month, but nobody punched — a day off */
        .day.rest {
            background: repeating-linear-gradient(
                -45deg, #faf9f7, #faf9f7 6px, #f4f3f0 6px, #f4f3f0 12px
            );
        }
        .day.rest .num { color: #a9a8a2; }

        .num { font-size: .78rem; font-weight: 650; color: var(--mute); margin-bottom: .35rem; }

        /* ---------- one person, one day: closed is a single line ---------- */
        .one { font-size: .8rem; border-bottom: 1px solid var(--line-2); }
        .one:last-of-type { border-bottom: none; }

        .one > summary {
            display: flex; gap: .4rem; align-items: baseline;
            padding: .28rem 0; cursor: pointer;
            line-height: 1.5; list-style: none;
        }
        .one > summary::-webkit-details-marker { display: none; }
        .one > summary:hover .nm { text-decoration: underline; }
        /* the browser default is a blue ring that fights the palette */
        .one > summary:focus { outline: none; }
        .one > summary:focus-visible {
            outline: 2px solid var(--ink); outline-offset: 1px; border-radius: 4px;
        }

        .caret {
            width: 9px; height: 9px; flex: none; color: #a9a8a2;
            transform: translateY(1px);
            transition: transform .15s ease;
        }
        .one[open] > summary .caret { transform: translateY(1px) rotate(90deg); }

        .one .nm {
            flex: 1; min-width: 0; font-weight: 600; color: var(--ink);
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .one[open] .nm { white-space: normal; }
        .one .hr { font-variant-numeric: tabular-nums; white-space: nowrap; color: var(--ok); }

        .one.plus .nm, .one.plus .hr, .one.plus .caret { color: var(--extra); }
        .one.bad  .nm, .one.bad  .hr, .one.bad  .caret { color: var(--bad); }

        .one .detail {
            padding: .4rem .55rem .5rem;
            margin: 0 0 .35rem;
            background: #faf9f7; border: 1px solid var(--line-2);
            border-radius: 7px; line-height: 1.5; color: var(--ink);
        }
        .line { display: grid; grid-template-columns: 4.7rem 1fr; }
        .line .lbl { color: var(--mute); }
        .line .val { font-variant-numeric: tabular-nums; }
        .worked .val { font-weight: 650; }
        .over .lbl, .over .val { color: var(--extra); }
        .over .val { font-weight: 650; }
        .problem { margin-top: .25rem; color: var(--bad); font-size: .78rem; font-weight: 600; }
        .none { color: #a9a8a2; }

        /* ---------- the "+N more" fold ---------- */
        .more { margin-top: .2rem; }
        .more > summary {
            cursor: pointer; font-size: .78rem; color: var(--mute); font-weight: 600;
            padding: .15rem 0; list-style: none;
        }
        .more > summary::-webkit-details-marker { display: none; }
        .more > summary:hover { color: var(--ink); text-decoration: underline; }

        /* ---------- the key under the calendar ----------
           One note per line. Side by side they wrapped mid sentence and
           were harder to read than plain text. */
        .key {
            background: var(--panel); border: 1px solid var(--line);
            border-radius: 10px; padding: 1rem 1.15rem; margin-top: 1.1rem;
            font-size: .88rem; color: var(--mute);
        }
        .key p { display: block; margin: 0 0 .5rem; }
        .key p:last-child { margin-bottom: 0; }
        .key p.gap { margin-top: .85rem; }
        .key b { color: var(--ink); font-weight: 600; }
        .key .dot {
            display: inline-block; width: .55rem; height: .55rem;
            border-radius: 50%; margin-right: .5rem;
        }
        .key .g { background: var(--ok); }
        .key .o { background: var(--extra); }
        .key .r { background: var(--bad); }
    </style>

    <form method="get" class="bar">
        <label>Month
            {{-- Safari has no month picker and shows a text box, so say what
                 it should look like there. --}}
            <input type="month" name="month" value="{{ $monthKey }}"
                   placeholder="2026-08" pattern="\d{4}-\d{2}">
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

    @unless ($hasData)
        <div class="info">
            Nothing recorded {{ $pin ? 'for this person ' : '' }}in {{ $month->format('F Y') }}.
            Pick another month, or check the Punch list.
        </div>
    @endunless

    <div class="card">
        <div class="scroll">
            <div class="cal">

                @foreach ($weekDays as $name)
                    <div class="head">{{ $name }}</div>
                @endforeach

                @foreach ($weeks as $week)
                    @foreach ($week as $day)
                        <div class="day {{ $day['inMonth'] ? ($day['off'] ? 'rest' : '') : 'pad' }}">

                            <div class="num">{{ $day['number'] }}</div>

                            @foreach (array_slice($day['people'], 0, $show) as $who)
                                @include('meraki.parts.person', ['who' => $who, 'open' => $detailed])
                            @endforeach

                            @if (count($day['people']) > $show)
                                <details class="more">
                                    <summary>+{{ count($day['people']) - $show }} more</summary>
                                    @foreach (array_slice($day['people'], $show) as $who)
                                        @include('meraki.parts.person', ['who' => $who, 'open' => $detailed])
                                    @endforeach
                                </details>
                            @endif

                        </div>
                    @endforeach
                @endforeach

            </div>
        </div>
    </div>

    <div class="key">
        <p><b>Click a name</b> to see its check in and check out times.</p>
        <p class="gap"><span class="dot g"></span>Green — normal day, up to {{ $shiftText }}.</p>
        <p><span class="dot o"></span>Orange — worked more than {{ $shiftText }}.</p>
        <p><span class="dot r"></span>Red — a punch is missing, so the hours are a guess.</p>
        <p class="gap"><b>Greyed boxes are days off.</b> Nobody at all punched on those
            {{ $offCount }} days, so they count as closed and are left out of every total.</p>
        <p><b>Worked</b> counts check in to check out. Breaks are not paid.</p>
        <p><b>Work day</b> is {{ $shift['start'] }} to {{ $shift['end'] }} — {{ $shiftText }}. Change it in Settings.</p>
    </div>

@endsection
