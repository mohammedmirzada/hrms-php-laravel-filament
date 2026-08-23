@extends('meraki.layout')

@section('title', 'Settings')
@section('subtitle', 'Settings')

@section('content')

    <style>
        .box {
            background: var(--panel); border: 1px solid var(--line);
            border-radius: 10px; padding: 1.4rem 1.5rem; max-width: 520px;
        }
        .box h2 { font-size: 1rem; font-weight: 650; margin: 0 0 .35rem; }
        .box p.help { color: var(--mute); font-size: .9rem; margin: 0 0 1.3rem; }
        .row { display: flex; gap: 1.5rem; flex-wrap: wrap; margin-bottom: 1.2rem; }
        .row input { width: 9rem; font-variant-numeric: tabular-nums; }
        .len {
            color: var(--mute); font-size: .9rem; margin: 0 0 1.3rem;
            padding: .6rem .8rem; background: #faf9f7;
            border: 1px solid var(--line-2); border-radius: 8px;
        }
        .len b { color: var(--ink); font-weight: 650; }
    </style>

    @if ($saved)
        <div class="ok">Shift saved.</div>
    @endif

    @if ($error)
        <div class="bad-box">{{ $error }}</div>
    @endif

    <div class="box">

        <h2>Work shift</h2>
        <p class="help">
            One shift for everybody. Anything worked over this many hours in a
            day is counted as extra time.
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
