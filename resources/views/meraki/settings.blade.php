@extends('meraki.layout')

@section('title', 'Settings')
@section('subtitle', 'Settings')

@section('content')

    <style>
        .box { background: #fff; border: 1px solid #d1d9e0; border-radius: 8px;
               padding: 1.25rem; max-width: 520px; }
        .box h2 { font-size: 1rem; margin: 0 0 .3rem; }
        .box p.help { color: #656d76; font-size: .88rem; margin: 0 0 1.1rem; }
        .row { display: flex; gap: 1.5rem; flex-wrap: wrap; margin-bottom: 1.1rem; }
        .row label { flex-direction: column; align-items: flex-start; gap: .3rem; }
        .row input { width: 9rem; font-variant-numeric: tabular-nums; }
        .len { color: #656d76; font-size: .88rem; margin: 0 0 1.1rem; }
        .len b { color: #1f2328; }
    </style>

    @if ($saved)
        <div class="ok">Shift saved.</div>
    @endif

    @if ($error)
        <div class="bad">{{ $error }}</div>
    @endif

    <div class="box">

        <h2>Work shift</h2>
        <p class="help">
            One shift for everybody. Anything worked over this many hours in a
            day is counted as overtime on the calendar.
        </p>

        <form method="post" action="{{ route('client.settings', ['client' => $client]) }}">
            @csrf

            <div class="row">
                <label>Start time
                    <input type="time" name="shift_start" value="{{ $shift['start'] }}" required>
                </label>
                <label>End time
                    <input type="time" name="shift_end" value="{{ $shift['end'] }}" required>
                </label>
            </div>

            <p class="len">
                Work day is <b>{{ $shiftText }}</b>.
                Anything over that is counted as extra time.
                @if ($isNight)
                    <br>Night shift — it ends the next morning.
                @endif
            </p>

            <button type="submit" class="go">Save</button>
        </form>

    </div>

@endsection
