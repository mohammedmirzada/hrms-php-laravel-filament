<?php

namespace App\Http\Controllers\Meraki;

use App\Http\Controllers\Controller;

use App\Models\Meraki;
use App\Models\MerakiUser;
use App\Support\MerakiSettings;
use Carbon\Carbon;
use Illuminate\Http\Request;

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

        return view('meraki.calendar', [
            'client'      => $client,
            'clientName'  => $config['name'],
            'month'       => $month,
            'monthKey'    => $month->format('Y-m'),
            'pin'         => $pin,
            'people'      => $names,
            'weeks'       => $this->weeks($month, $days),
            'weekDays'    => $this->weekDayNames(),
            'shift'       => $shift,
            'shiftText'   => MerakiSettings::readable($shiftLength),
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

                // No IN at all that day? Show the first punch as the start.
                $first ??= $list[0]->punched_at;

                $overtime = max(0, $worked - $shiftLength);

                $days[$date][] = [
                    'pin'          => $pin,
                    'name'         => $names[$pin] ?? ('PIN ' . $pin),
                    'in'           => $first->format('g:i A'),
                    'out'          => $last?->format('g:i A'),   // null = never checked out
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
     */
    private function weeks(Carbon $month, array $days): array {

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

            $week[] = [
                'date'    => $date,
                'number'  => $cursor->day,
                'inMonth' => $cursor->month === $month->month,
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
            'rows'       => $rows,
        ]);
    }

    // ---------------------------------------------------------------- shared

    /** punch id => 'IN' or 'OUT'. */
    private function directions($punches): array {

        $mode = config('meraki.punch_state', 'auto');

        // 'auto': trust the device as soon as it actually starts sending a
        // state. While every punch arrives as 0 the device is clearly not
        // telling us, so fall back to counting.
        if ($mode === 'auto') {
            $mode = $punches->contains(fn ($p) => $p->status > 0) ? 'device' : 'alternate';
        }

        $useDevice = $mode === 'device';

        $seen = [];   // pin|date => how many punches so far that day
        $out  = [];

        foreach ($punches as $punch) {

            $key = $punch->pin . '|' . $punch->punched_at->toDateString();

            $seen[$key] = ($seen[$key] ?? 0) + 1;

            $out[$punch->id] = $useDevice
                ? (self::DEVICE_STATES[$punch->status] ?? 'IN')
                : ($seen[$key] % 2 === 1 ? 'IN' : 'OUT');
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
