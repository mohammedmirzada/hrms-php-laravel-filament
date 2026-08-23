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
 *   calendar()  month grid, every person on the day they worked
 *   log()       plain list, one row per punch, newest first
 *
 * Hours are counted by adding up every IN -> OUT pair, so a break in the
 * middle is not paid. Overtime is worked hours above the shift length, set
 * on the Settings page.
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

    // -------------------------------------------------------------- calendar

    public function calendar(Request $request, string $client) {

        [$config, $sn] = $this->client($client);

        $month = $this->month($request->query('month'));
        $pin   = $request->query('pin');

        $names   = $this->names($sn);
        $punches = $this->punches($sn, $month, $pin);

        $shift       = MerakiSettings::shift($client);
        $shiftLength = MerakiSettings::shiftMinutes($client);

        $days = $this->days($punches, $names, $shiftLength);

        // Days off are worked out from everybody, not from the filtered person
        $busy = $this->busyDates($sn, $month);

        return view('meraki.calendar', [
            'client'      => $client,
            'clientName'  => $config['name'],
            'month'       => $month,
            'monthKey'    => $month->format('Y-m'),
            'pin'         => $pin,
            'people'      => $names,
            'weeks'       => $this->weeks($month, $days, $busy),
            'weekDays'    => $this->weekDayNames(),
            'offCount'    => $this->offDayCount($month, $busy),
            'shift'       => $shift,
            'shiftText'   => MerakiSettings::readable($shiftLength),

            // With everybody on screen the day boxes would grow one block per
            // person and the month would be metres tall. So everybody gets one
            // short line each, and the full block is for one chosen person.
            'detailed'    => $pin !== null && $pin !== '',

            // An empty grid on its own looks broken. Say so instead.
            'hasData'     => count($days) > 0,
        ]);
    }

    // -------------------------------------------------------------- overtime

    /**
     * Month totals per person: how long they worked, how much of it was over
     * the work day, and how much they were short of it.
     *
     * Short time only counts days they actually came. A day nobody punched is
     * a day off as far as this page knows — the device cannot tell the
     * difference between a holiday and staying home.
     */
    public function overtime(Request $request, string $client) {

        [$config, $sn] = $this->client($client);

        $month = $this->month($request->query('month'));

        $names       = $this->names($sn);
        $punches     = $this->punches($sn, $month, null);
        $shiftLength = MerakiSettings::shiftMinutes($client);

        $days = $this->days($punches, $names, $shiftLength);

        $totals = [];

        foreach ($days as $date => $people) {

            foreach ($people as $who) {

                $pin = $who['pin'];

                $totals[$pin] ??= [
                    'name'     => $who['name'],
                    'days'     => 0,
                    'worked'   => 0,
                    'extra'    => 0,
                    'short'    => 0,
                    'problems' => 0,
                ];

                $totals[$pin]['days']     += 1;
                $totals[$pin]['worked']   += $who['worked'];
                $totals[$pin]['extra']    += $who['overtime'];
                $totals[$pin]['short']    += max(0, $shiftLength - $who['worked']);
                $totals[$pin]['problems'] += $who['problem'] ? 1 : 0;
            }
        }

        // Words, not numbers, for everything on screen
        foreach ($totals as $pin => $row) {
            $totals[$pin]['workedText'] = MerakiSettings::readable($row['worked']);
            $totals[$pin]['extraText']  = MerakiSettings::readable($row['extra']);
            $totals[$pin]['shortText']  = MerakiSettings::readable($row['short']);
        }

        uasort($totals, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return view('meraki.overtime', [
            'client'      => $client,
            'clientName'  => $config['name'],
            'month'       => $month,
            'monthKey'    => $month->format('Y-m'),
            'rows'        => $totals,
            'shift'       => MerakiSettings::shift($client),
            'shiftText'   => MerakiSettings::readable($shiftLength),
            'totalExtra'  => MerakiSettings::readable(array_sum(array_column($totals, 'extra'))),
            'totalShort'  => MerakiSettings::readable(array_sum(array_column($totals, 'short'))),
            'totalWorked' => MerakiSettings::readable(array_sum(array_column($totals, 'worked'))),
        ]);
    }

    /**
     * One entry per person per day they punched:
     * first in, last out, worked minutes, overtime minutes.
     */
    private function days($punches, $names, int $shiftLength): array {

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

                $overtime = max(0, $worked - $shiftLength);

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
     * The month as rows of seven days, padded out so every week is full.
     *
     * A date in the month that nobody punched at all is marked a day off.
     * The device is the only thing that knows anything, so no punches from
     * anyone is the only signal there is that the place was shut.
     *
     * $allDays is always the whole company's month, never the filtered person,
     * so picking one name from the filter does not turn everyone else's normal
     * working days into days off.
     */
    private function weeks(Carbon $month, array $days, array $allDays): array {

        $startsOn = (int) config('meraki.week_starts_on', 6);

        $cursor = $month->copy()->startOfMonth();

        while ($cursor->dayOfWeek !== $startsOn) {
            $cursor->subDay();
        }

        $lastDay = $month->copy()->endOfMonth()->startOfDay();

        $weeks = [];
        $week  = [];

        while ($cursor <= $lastDay || count($week) > 0) {

            $date = $cursor->toDateString();

            $inMonth = $cursor->month === $month->month;

            $week[] = [
                'date'    => $date,
                'number'  => $cursor->day,
                'inMonth' => $inMonth,
                'off'     => $inMonth && empty($allDays[$date]),
                'people'  => $days[$date] ?? [],
            ];

            if (count($week) === 7) {
                $weeks[] = $week;
                $week    = [];

                if ($cursor >= $lastDay) {
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

        $month = $this->month($request->query('month'));
        $pin   = $request->query('pin');

        $names   = $this->names($sn);
        $punches = $this->punches($sn, $month, $pin);

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
            'month'      => $month,
            'monthKey'   => $month->format('Y-m'),
            'pin'        => $pin,
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

    // ---------------------------------------------------------------- shared

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
     * Every date in the month that anybody punched on, as ['Y-m-d' => true].
     *
     * Always the whole device, never one person — a date nobody touched is
     * what this system calls a day off.
     */
    private function busyDates(string $sn, Carbon $month): array {

        return Meraki::where('device_sn', $sn)
            ->whereBetween('punched_at', [
                $month->copy()->startOfMonth(),
                $month->copy()->endOfMonth()->endOfDay(),
            ])
            ->selectRaw('DATE(punched_at) as d')
            ->distinct()
            ->pluck('d')
            ->mapWithKeys(fn ($date) => [(string) $date => true])
            ->all();
    }

    /** How many days of the month nobody punched on. */
    private function offDayCount(Carbon $month, array $busy): int {

        $off    = 0;
        $cursor = $month->copy()->startOfMonth();
        $last   = $month->copy()->endOfMonth()->startOfDay();

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

    /** One month of punches, oldest first, for one person or everybody. */
    private function punches(string $sn, Carbon $month, ?string $pin) {

        return Meraki::where('device_sn', $sn)
            ->whereBetween('punched_at', [
                $month->copy()->startOfMonth(),
                $month->copy()->endOfMonth()->endOfDay(),
            ])
            ->when($pin, fn ($q) => $q->where('pin', $pin))
            ->orderBy('pin')
            ->orderBy('punched_at')
            ->get();
    }

    private function month(?string $value): Carbon {

        try {
            return Carbon::createFromFormat('Y-m', (string) $value)->startOfMonth();
        } catch (\Throwable $e) {
            return Carbon::now()->startOfMonth();
        }
    }

}
