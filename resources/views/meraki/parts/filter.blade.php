{{--
    The filter bar, the same on all three pages.

    Two dates and any number of employees. No employee ticked means everybody,
    which is what people expect when they have not touched it.

    $from, $to   the dates now being shown, as YYYY-MM-DD
    $pins        the employees now picked, as a list of pins
    $people      pin => name, everyone the device knows
--}}
@php
    // What the closed dropdown says. One name reads better than "1 picked";
    // past that the names would be longer than the box.
    $picked = count($pins) === 1
        ? ($people[$pins[0]] ?? 'PIN ' . $pins[0])
        : (count($pins) ? count($pins) . ' picked' : 'Everyone');
@endphp

<form method="get" class="bar">

    {{-- No min/max on these on purpose. Locking one to the other means you
         have to change them in the right order to move the window forward,
         and a backwards range is turned round on the way in anyway. --}}
    <label>From
        <input type="date" name="from" value="{{ $from }}">
    </label>

    <label>To
        <input type="date" name="to" value="{{ $to }}">
    </label>

    <div class="pickwrap">

        <span class="cap">Employee</span>

        <details class="drop">

            <summary>
                <span class="what {{ count($pins) ? '' : 'all' }}">{{ $picked }}</span>
                <svg class="chev" viewBox="0 0 10 10" aria-hidden="true">
                    <path d="M2 3.5 L5 6.5 L8 3.5" fill="none" stroke="currentColor"
                          stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </summary>

            {{-- Still inside the form while it is shut, so the ticks are sent
                 whether the list is open or closed. --}}
            <div class="panel">

                <span class="top">
                    Tick nobody to see everyone
                    @if (count($pins))
                        <a href="{{ request()->url() }}?from={{ $from }}&to={{ $to }}">Clear</a>
                    @endif
                </span>

                @forelse ($people as $p => $name)
                    <label class="tick">
                        <input type="checkbox" name="pins[]" value="{{ $p }}"
                               @checked(in_array((string) $p, $pins, true))>
                        <span>{{ $name }}</span>
                    </label>
                @empty
                    <span class="dimtick">No names from the device yet</span>
                @endforelse

            </div>

        </details>

    </div>

    <button type="submit" class="go">Show</button>

</form>
