@extends('meraki.layout')

@section('title', 'Attendance ' . $month->format('F Y'))
@section('subtitle', $month->format('F Y'))

@section('content')

    <style>
        .scroll { overflow-x: auto; }
        .cal { display: grid; grid-template-columns: repeat(7, minmax(185px, 1fr)); min-width: 1290px; }
        .head {
            padding: .5rem .7rem; font-size: .78rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: .04em; color: #656d76;
            background: #f6f8fa; border-bottom: 1px solid #d1d9e0;
        }
        .day {
            border-right: 1px solid #eaeef2; border-bottom: 1px solid #eaeef2;
            padding: .5rem .6rem; min-height: 110px;
        }
        .day:nth-child(7n) { border-right: none; }
        .day.off { background: #fafbfc; }
        .num { font-size: .8rem; font-weight: 600; color: #656d76; margin-bottom: .4rem; }
        .day.off .num { color: #c9ced4; }

        .who {
            font-size: .82rem; line-height: 1.5;
            padding: .4rem .5rem; margin-bottom: .4rem;
            background: #f6f8fa; border-radius: 6px;
        }
        .who:last-child { margin-bottom: 0; }
        .who .name { font-weight: 600; color: #1f2328; margin-bottom: .15rem; }

        .line { display: grid; grid-template-columns: 4.7rem 1fr; }
        .line .lbl { color: #656d76; }
        .line .val { font-variant-numeric: tabular-nums; }

        .worked .val { font-weight: 600; }
        .over { color: #bc4c00; }
        .over .lbl, .over .val { color: #bc4c00; }
        .over .val { font-weight: 600; }

        .problem {
            margin-top: .2rem; color: #cf222e; font-size: .78rem; font-weight: 600;
        }
        .none { color: #8b949e; }
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
        <div class="scroll">
            <div class="cal">

                @foreach ($weekDays as $name)
                    <div class="head">{{ $name }}</div>
                @endforeach

                @foreach ($weeks as $week)
                    @foreach ($week as $day)
                        <div class="day {{ $day['inMonth'] ? '' : 'off' }}">

                            <div class="num">{{ $day['number'] }}</div>

                            @foreach ($day['people'] as $who)
                                <div class="who">

                                    <div class="name">{{ $who['name'] }}</div>

                                    <div class="line">
                                        <span class="lbl">First in</span>
                                        <span class="val">{{ $who['in'] }}</span>
                                    </div>

                                    <div class="line">
                                        <span class="lbl">Last out</span>
                                        <span class="val">
                                            {{ $who['out'] ?? '' }}
                                            @unless ($who['out']) <span class="none">not yet</span> @endunless
                                        </span>
                                    </div>

                                    <div class="line worked">
                                        <span class="lbl">Worked</span>
                                        <span class="val">{{ $who['workedText'] }}</span>
                                    </div>

                                    @if ($who['overtime'] > 0)
                                        <div class="line over">
                                            <span class="lbl">Extra</span>
                                            <span class="val">{{ $who['overtimeText'] }}</span>
                                        </div>
                                    @endif

                                    @if ($who['problem'])
                                        <div class="problem">{{ $who['problem'] }}</div>
                                    @endif

                                </div>
                            @endforeach

                        </div>
                    @endforeach
                @endforeach

            </div>
        </div>
    </div>

    <p class="note">
        Work day is {{ $shift['start'] }} to {{ $shift['end'] }} — {{ $shiftText }} a day.
        Change it in Settings.<br>
        <b>Worked</b> adds up every check in to check out. Time away in the middle is not
        counted, so Worked can be less than First in to Last out.<br>
        <b>Extra</b> is the time worked over {{ $shiftText }} that day.<br>
        <b>Red text</b> means a punch is missing, so Worked for that day is not the full story.
        Open Punch list to see every press.
    </p>

@endsection
