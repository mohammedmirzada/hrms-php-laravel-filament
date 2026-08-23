{{--
    One person on one day.

    Closed it is a single line — name and hours worked — so a day box stays
    short no matter how many people there are. Click it and the same detail
    as before opens underneath.

    $who   the day's numbers for this person
    $open  start opened (used when one person is picked from the filter)
--}}
<details class="one {{ $who['problem'] ? 'bad' : ($who['overtime'] > 0 ? 'plus' : '') }}" @if ($open) open @endif>

    <summary>
        <svg class="caret" viewBox="0 0 10 10" aria-hidden="true">
            <path d="M3 1.5 L7 5 L3 8.5" fill="none" stroke="currentColor"
                  stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span class="nm">{{ $who['name'] }}</span>
        <span class="hr">{{ $who['workedText'] }}</span>
    </summary>

    <div class="detail">

        <div class="line">
            <span class="lbl">First in</span>
            <span class="val">
                {{ $who['in'] ?? '' }}
                @unless ($who['in']) <span class="none">never pressed</span> @endunless
            </span>
        </div>

        <div class="line">
            <span class="lbl">Last out</span>
            <span class="val">
                {{ $who['out'] ?? '' }}
                @unless ($who['out']) <span class="none">never pressed</span> @endunless
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

</details>
