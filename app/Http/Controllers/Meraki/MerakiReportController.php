<?php

namespace App\Http\Controllers\Meraki;

use App\Http\Controllers\Controller;

use App\Models\Meraki;
use App\Models\MerakiUser;
use App\Support\MerakiSettings;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Attendance for one client.
 *
 *   calendar()  day grid over the chosen dates, everybody on the day they worked
 *   overtime()  totals per person over the chosen dates
 *   log()       plain list, one row per punch, newest first
 *
 * All three take the same filters: a date range (from / to) and any number of
 * employees. Leaving the employees empty means everybody.
 *
 * Hours are counted by adding up every IN -> OUT pair, so a break in the
 * middle is not paid. Overtime is worked hours above the length of that
 * person's shift, set on the Settings page.
 *
 * How IN / OUT is decided is set by config('meraki.punch_state').
 */
class MerakiReportController extends Controller {

    /** ZK punch state codes, folded down to just in or out. */
    private const DEVICE_STATES = [
        0 => 'IN',    // check in
        1 => 'OUT',   // check out
        2 => 'OUT',   // break out
        3 => 'IN',    // break in
        4 => 'IN',    // overtime in
        5 => 'OUT',   // overtime out
    ];

    /**
     * Longest range anybody may ask for. A range of years would build a grid
     * nobody can read out of a query nobody wants to wait for.
     */
    private const MAX_DAYS = 366;

    // -------------------------------------------------------------- calendar

    public function calendar(Request $request, string $client) {

        [$config, $sn] = $this->client($client);

        [$from, $to] = $this->range($request);

        $names = $this->people($sn, $from, $to);
        $pins  = $this->pins($request, $names);

        $punches = $this->punches($sn, $from, $to, $pins);

        $days = $this->days($punches, $names, $client);

        // Days off are worked out from everybody, not from the chosen people
        $busy = $this->busyDates($sn, $from, $to);

        return view('meraki.calendar', [
            'client'     => $client,
            'clientName' => $config['name'],
            'from'       => $from->toDateString(),
            'to'         => $to->toDateString(),
            'label'      => $this->rangeLabel($from, $to),
            'pins'       => $pins,
            'people'     => $names,
            'weeks'      => $this->weeks($from, $to, $days, $busy),
            'weekDays'   => $this->weekDayNames(),
            'offCount'   => $this->offDayCount($from, $to, $busy),
            'shifts'     => MerakiSettings::shifts($client),

            // With everybody on screen the day boxes would grow one block per
            // person and the month would be metres tall. So everybody gets one
            // short line each, and full blocks are for the people picked.
            'detailed'   => count($pins) > 0,

            // An empty grid on its own looks broken. Say so instead.
            'hasData'    => count($days) > 0,
        ]);
    }

    // -------------------------------------------------------------- overtime

    /**
     * Totals per person over the chosen dates: how long they worked, how much
     * of it was over their work day, and how much they were short of it.
     *
     * Short time only counts days they actually came. A day nobody punched is
     * a day off as far as this page knows — the device cannot tell the
     * difference between a holiday and staying home.
     */
    public function overtime(Request $request, string $client) {

        [$config, $sn] = $this->client($client);

        [$from, $to] = $this->range($request);

        $names = $this->people($sn, $from, $to);
        $pins  = $this->pins($request, $names);

        $punches = $this->punches($sn, $from, $to, $pins);

        $days = $this->days($punches, $names, $client);

        $totals = [];

        foreach ($days as $date => $people) {

            foreach ($people as $who) {

                $pin = $who['pin'];

                $totals[$pin] ??= [
                    'name'      => $who['name'],
                    'shifts'    => [],
                    'days'      => 0,
                    'worked'    => 0,
                    'extra'     => 0,
                    'short'     => 0,
                    'problems'  => 0,
                ];

                // Somebody moved mid-range worked under more than one shift,
                // and the row has to say so rather than name only the first.
                $totals[$pin]['shifts'][$who['shiftName']] = $who['shiftText'];

                $totals[$pin]['days']     += 1;
                $totals[$pin]['worked']   += $who['worked'];
                $totals[$pin]['extra']    += $who['overtime'];
                $totals[$pin]['short']    += $who['short'];
                $totals[$pin]['problems'] += $who['problem'] ? 1 : 0;
            }
        }

        // Words, not numbers, for everything on screen
        foreach ($totals as $pin => $row) {

            $totals[$pin]['workedText'] = MerakiSettings::readable($row['worked']);
            $totals[$pin]['extraText']  = MerakiSettings::readable($row['extra']);
            $totals[$pin]['shortText']  = MerakiSettings::readable($row['short']);

            // One shift: name it, with its hours beside it. More than one:
            // name them all and drop the hours, which are no longer one number.
            $totals[$pin]['shiftName'] = implode(', ', array_keys($row['shifts']));
            $totals[$pin]['shiftText'] = count($row['shifts']) === 1
                ? reset($row['shifts'])
                : '';
        }

        uasort($totals, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return view('meraki.overtime', [
            'client'      => $client,
            'clientName'  => $config['name'],
            'from'        => $from->toDateString(),
            'to'          => $to->toDateString(),
            'label'       => $this->rangeLabel($from, $to),
            'pins'        => $pins,
            'people'      => $names,
            'rows'        => $totals,
            'shifts'      => MerakiSettings::shifts($client),
            'totalExtra'  => MerakiSettings::readable(array_sum(array_column($totals, 'extra'))),
            'totalShort'  => MerakiSettings::readable(array_sum(array_column($totals, 'short'))),
            'totalWorked' => MerakiSettings::readable(array_sum(array_column($totals, 'worked'))),
        ]);
    }

    /**
     * One entry per person per day they punched:
     * first in, last out, worked minutes, overtime, short time.
     *
     * The shift is looked up per person, so two people on the same day can be
     * measured against different hours.
     */
    private function days($punches, $names, string $client): array {

        $directions = $this->directions($punches);

        // date => pin => list of punches
        $grouped = [];

        foreach ($punches as $punch) {
            $grouped[$punch->punched_at->toDateString()][$punch->pin][] = $punch;
        }

        $days = [];

        foreach ($grouped as $date => $byPin) {

            foreach ($byPin as $pin => $list) {

                $worked  = 0;
                $openAt  = null;    // start of the pair we are inside of
                $everIn  = false;   // has this person checked in at all today
                $noOut   = false;   // went in and never came out
                $noIn    = false;   // the day starts with a check out
                $first   = null;
                $last    = null;

                foreach ($list as $punch) {

                    $direction = $directions[$punch->id] ?? 'IN';

                    if ($direction === 'IN') {

                        $everIn = true;

                        // Already in? Keep the earlier one, ignore this punch.
                        // People press the finger two or three times in a row.
                        if ($openAt === null) {
                            $openAt = $punch->punched_at;
                            $first ??= $punch->punched_at;
                        }

                        continue;
                    }

                    if ($openAt === null) {

                        // Already out. Same double press as above, just on the
                        // other key — ignore it, it is not a missing check in.
                        if ($everIn) {
                            continue;
                        }

                        // Nothing before it at all: the day really does start
                        // with a check out.
                        $noIn = true;
                        continue;
                    }

                    // Carbon 3 gives a signed float here, so round it down to
                    // whole minutes and never let a bad pair go negative.
                    $worked += max(0, (int) round($openAt->diffInMinutes($punch->punched_at)));
                    $last    = $punch->punched_at;
                    $openAt  = null;
                }

                // Went in and never came out.
                if ($openAt !== null) {
                    $noOut = true;
                }

                $shift = $this->shiftOf($client, $list, $pin);

                $overtime = max(0, $worked - $shift['minutes']);

                // The grace period forgives being a few minutes light, so
                // arriving five minutes late is not held against anybody.
                $short = max(0, $shift['minutes'] - $worked - $shift['grace']);

                $days[$date][] = [
                    'pin'          => $pin,
                    'name'         => $names[$pin] ?? ('PIN ' . $pin),
                    // Both null when there is nothing honest to show. Better a
                    // blank than a check out time sitting under "First in".
                    'in'           => $first?->format('g:i A'),
                    'out'          => $last?->format('g:i A'),
                    'worked'       => $worked,
                    'workedText'   => MerakiSettings::readable($worked),
                    'overtime'     => $overtime,
                    'overtimeText' => MerakiSettings::readable($overtime),
                    'short'        => $short,
                    'shortText'    => MerakiSettings::readable($short),
                    'shiftName'    => $shift['name'],
                    'shiftText'    => $shift['text'],
                    'problem'      => $this->problem($noIn, $noOut),
                ];
            }

            usort($days[$date], fn ($a, $b) => strcasecmp($a['name'], $b['name']));
        }

        return $days;
    }

    /**
     * Plain words for a day with a punch missing, so nobody has to guess why
     * the hours look small. Null when the day is fine.
     */
    private function problem(bool $noIn, bool $noOut): ?string {

        if ($noIn && $noOut) {
            return 'Missing a check in and a check out';
        }

        if ($noOut) {
            return 'Missing a check out';
        }

        if ($noIn) {
            return 'Missing a check in';
        }

        return null;
    }

    /**
     * The chosen dates as rows of seven days, padded out so every week is full.
     * A range of one month looks exactly like the old month grid; a range that
     * crosses months just keeps going.
     *
     * A date inside the range that nobody punched at all is marked a day off.
     * The device is the only thing that knows anything, so no punches from
     * anyone is the only signal there is that the place was shut.
     *
     * $allDays is always the whole company, never the chosen people, so
     * picking a name does not turn everyone else's working days into days off.
     */
    private function weeks(Carbon $from, Carbon $to, array $days, array $allDays): array {

        $startsOn = (int) config('meraki.week_starts_on', 6);

        $cursor = $from->copy()->startOfDay();

        while ($cursor->dayOfWeek !== $startsOn) {
            $cursor->subDay();
        }

        $start = $from->copy()->startOfDay();
        $end   = $to->copy()->startOfDay();

        $weeks = [];
        $week  = [];

        while (true) {

            $date = $cursor->toDateString();

            $inRange = $cursor->betweenIncluded($start, $end);

            // A date that has not happened yet is not a day off, it is just
            // the future. Without this the rest of the month greys out and
            // looks like a three week holiday.
            $past = $cursor->lessThanOrEqualTo(Carbon::today());

            $week[] = [
                'date'    => $date,
                'number'  => $cursor->day,
                // A range can cross months, so say which month on its 1st.
                'month'   => $cursor->day === 1 ? $cursor->format('M') : null,
                'inRange' => $inRange,
                'off'     => $inRange && $past && empty($allDays[$date]),
                'people'  => $days[$date] ?? [],
            ];

            if (count($week) === 7) {

                $weeks[] = $week;
                $week    = [];

                if ($cursor >= $end) {
                    break;
                }
            }

            $cursor->addDay();
        }

        return $weeks;
    }

    /** Column headings, starting on the configured day. */
    private function weekDayNames(): array {

        $startsOn = (int) config('meraki.week_starts_on', 6);

        $day = Carbon::now()->startOfWeek(Carbon::SUNDAY)->addDays($startsOn);

        return collect(range(0, 6))
            ->map(fn ($i) => $day->copy()->addDays($i)->format('D'))
            ->all();
    }

    // ------------------------------------------------------------------- log

    /** The old flat list: one row per punch, nothing merged, nothing dropped. */
    public function log(Request $request, string $client) {

        [$config, $sn] = $this->client($client);

        [$from, $to] = $this->range($request);

        $names = $this->people($sn, $from, $to);
        $pins  = $this->pins($request, $names);

        $punches = $this->punches($sn, $from, $to, $pins);

        $directions = $this->directions($punches);

        $rows = [];

        foreach ($punches as $punch) {
            $rows[] = [
                'name'      => $names[$punch->pin] ?? ('PIN ' . $punch->pin),
                'date'      => $punch->punched_at->toDateString(),
                'day'       => $punch->punched_at->format('D'),
                'time'      => $punch->punched_at->format('g:i A'),
                'direction' => $directions[$punch->id] ?? 'IN',
                'sortKey'   => $punch->punched_at->format('Y-m-d H:i:s'),
            ];
        }

        // Newest punch first. Directions were worked out in time order above,
        // so flipping the display order is safe.
        usort($rows, fn ($a, $b) => [$b['sortKey'], $a['name']] <=> [$a['sortKey'], $b['name']]);

        return view('meraki.report', [
            'client'     => $client,
            'clientName' => $config['name'],
            'from'       => $from->toDateString(),
            'to'         => $to->toDateString(),
            'label'      => $this->rangeLabel($from, $to),
            'pins'       => $pins,
            'people'     => $names,
            'rows'       => $this->page($request, $rows),
        ]);
    }

    /**
     * Cut the log into pages.
     *
     * Sliced here rather than in the query on purpose: working out IN or OUT
     * by counting needs a person's whole day, and a database LIMIT would chop
     * days in half and flip the answers on every page boundary.
     */
    private function page(Request $request, array $rows): LengthAwarePaginator {

        $perPage = 100;

        $page = max(1, (int) $request->query('page', 1));
        $last = max(1, (int) ceil(count($rows) / $perPage));
        $page = min($page, $last);

        return new LengthAwarePaginator(
            array_slice($rows, ($page - 1) * $perPage, $perPage),
            count($rows),
            $perPage,
            $page,
            [
                'path'  => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    // --------------------------------------------------------------- filters

    /**
     * The dates being looked at, as whole days. Defaults to this month.
     *
     * ?month=2026-08 is still understood, so old links and bookmarks from
     * before the range filter existed keep working.
     */
    private function range(Request $request): array {

        $from = $this->date($request->query('from'));
        $to   = $this->date($request->query('to'));

        if ($from === null && $to === null) {

            $month = $this->month($request->query('month'));

            return [
                $month->copy()->startOfMonth(),
                $month->copy()->endOfMonth()->startOfDay(),
            ];
        }

        // One end on its own still works: it fills the rest of its own month.
        $from ??= $to->copy()->startOfMonth();
        $to   ??= $from->copy()->endOfMonth()->startOfDay();

        if ($to->lessThan($from)) {
            [$from, $to] = [$to, $from];
        }

        if ($from->diffInDays($to) > self::MAX_DAYS) {
            $to = $from->copy()->addDays(self::MAX_DAYS);
        }

        return [$from, $to];
    }

    /**
     * The chosen employees, as pins. Empty means everybody.
     *
     * Only people the device actually knows get through, so a hand typed pin
     * in the URL cannot quietly produce an empty page.
     */
    private function pins(Request $request, $names): array {

        $raw = $request->query('pins', $request->query('pin'));

        $wanted = array_filter(
            array_map(fn ($p) => trim((string) $p), (array) $raw),
            fn ($p) => $p !== ''
        );

        $known = array_map('strval', $names->keys()->all());

        // Unique, or the same name sent twice would read as "2 picked".
        return array_values(array_unique(array_intersect($wanted, $known)));
    }

    /** Something readable for the title: a whole month, a day, or a range. */
    private function rangeLabel(Carbon $from, Carbon $to): string {

        if ($from->isSameDay($to)) {
            return $from->format('j M Y');
        }

        $wholeMonth = $from->day === 1
            && $to->isSameDay($to->copy()->endOfMonth()->startOfDay())
            && $from->isSameMonth($to);

        if ($wholeMonth) {
            return $from->format('F Y');
        }

        return $from->format('j M Y') . ' – ' . $to->format('j M Y');
    }

    // ---------------------------------------------------------------- shared

    /**
     * The shift one person's day was measured against.
     *
     * Taken from the punches themselves: the device stamps each one with the
     * shift that person was on at the time, so moving them to another shift
     * tomorrow leaves every day before it exactly as it was.
     *
     * Punches recorded before that stamp existed have nothing to go on, so
     * those fall back to the shift the person is on now.
     */
    private function shiftOf(string $client, $list, $pin): array {

        foreach ($list as $punch) {

            if ($punch->shift_id !== null && $punch->shift_id !== '') {
                return MerakiSettings::shift($client, (string) $punch->shift_id);
            }
        }

        return MerakiSettings::shiftFor($client, (string) $pin);
    }

    /**
     * punch id => 'IN' or 'OUT'.
     *
     * Decided one person, one day at a time. That matters: the choice must not
     * depend on what happens to be in the query, or picking one person from the
     * filter would show them different in/out times than the Everyone view.
     * It also handles the day the punch state key was switched on — days before
     * it keep counting, days after it use the real states.
     */
    private function directions($punches): array {

        $mode = config('meraki.punch_state', 'auto');

        // pin|date => that person's punches that day, oldest first
        $byPersonDay = [];

        foreach ($punches as $punch) {
            $byPersonDay[$punch->pin . '|' . $punch->punched_at->toDateString()][] = $punch;
        }

        $out = [];

        foreach ($byPersonDay as $list) {

            // 'auto': trust the device as soon as it sends a state at all.
            // While every punch that day arrives as 0 the device is clearly
            // not telling us, so fall back to counting.
            $useDevice = match ($mode) {
                'device'    => true,
                'alternate' => false,
                default     => (bool) array_filter($list, fn ($p) => $p->status > 0),
            };

            $seen = 0;

            foreach ($list as $punch) {

                $seen++;

                $out[$punch->id] = $useDevice
                    ? (self::DEVICE_STATES[$punch->status] ?? 'IN')
                    : ($seen % 2 === 1 ? 'IN' : 'OUT');
            }
        }

        return $out;
    }

    /** Config block and serial number, or a 404. */
    private function client(string $client): array {

        $config = config("meraki.clients.{$client}");

        abort_if(
            ! is_array($config) || empty($config['device_sn']),
            404,
            "Unknown client [{$client}]. If you just deployed, run: php artisan config:clear"
        );

        return [$config, $config['device_sn']];
    }

    /**
     * Every date in the range that anybody punched on, as ['Y-m-d' => true].
     *
     * Always the whole device, never the chosen people — a date nobody touched
     * is what this system calls a day off.
     */
    private function busyDates(string $sn, Carbon $from, Carbon $to): array {

        return Meraki::where('device_sn', $sn)
            ->whereBetween('punched_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->selectRaw('DATE(punched_at) as d')
            ->distinct()
            ->pluck('d')
            ->mapWithKeys(fn ($date) => [(string) $date => true])
            ->all();
    }

    /** How many days in the range nobody punched on. Future dates do not count. */
    private function offDayCount(Carbon $from, Carbon $to, array $busy): int {

        $off    = 0;
        $cursor = $from->copy()->startOfDay();
        $last   = $to->copy()->startOfDay()->min(Carbon::today());

        while ($cursor <= $last) {

            if (empty($busy[$cursor->toDateString()])) {
                $off++;
            }

            $cursor->addDay();
        }

        return $off;
    }

    private function names(string $sn) {

        return MerakiUser::where('device_sn', $sn)
            ->orderBy('name')
            ->pluck('name', 'pin');
    }

    /**
     * Everyone the filter can offer: the names the device has sent, plus any
     * pin that punched in this range but has no name yet.
     *
     * Without the second half those punches show up in the reports as "PIN 7"
     * with no way to filter to them — the device sends attendance long before
     * it sends the name list.
     */
    private function people(string $sn, Carbon $from, Carbon $to) {

        $names = $this->names($sn);

        $seen = Meraki::where('device_sn', $sn)
            ->whereBetween('punched_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->select('pin')
            ->distinct()
            ->pluck('pin');

        foreach ($seen as $pin) {
            if (! isset($names[$pin])) {
                $names[$pin] = 'PIN ' . $pin;
            }
        }

        return $names->sortBy(fn ($name) => mb_strtolower($name));
    }

    /** Punches over the range, oldest first, for the chosen people or everybody. */
    private function punches(string $sn, Carbon $from, Carbon $to, array $pins) {

        return Meraki::where('device_sn', $sn)
            ->whereBetween('punched_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->when($pins, fn ($q) => $q->whereIn('pin', $pins))
            ->orderBy('pin')
            ->orderBy('punched_at')
            ->get();
    }

    private function date(?string $value): ?Carbon {

        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($value))) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', trim($value))->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function month(?string $value): Carbon {

        try {
            return Carbon::createFromFormat('Y-m', (string) $value)->startOfMonth();
        } catch (\Throwable $e) {
            return Carbon::now()->startOfMonth();
        }
    }

}
