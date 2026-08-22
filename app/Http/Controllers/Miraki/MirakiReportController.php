<?php

namespace App\Http\Controllers\Miraki;

use App\Http\Controllers\Controller;

use App\Models\Miraki;
use App\Models\MirakiUser;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Attendance report for one client.
 *
 * A plain log: one row per punch — name, date, time, in or out.
 * Nothing is merged, nothing is dropped. Ten punches means ten rows.
 *
 * How IN / OUT is decided is set by config('miraki.punch_state').
 */
class MirakiReportController extends Controller {

    /** ZK punch state codes, folded down to just in or out. */
    private const DEVICE_STATES = [
        0 => 'IN',    // check in
        1 => 'OUT',   // check out
        2 => 'OUT',   // break out
        3 => 'IN',    // break in
        4 => 'IN',    // overtime in
        5 => 'OUT',   // overtime out
    ];

    public function index(Request $request, string $client) {

        $config = config("miraki.clients.{$client}");

        abort_if(
            ! is_array($config) || empty($config['device_sn']),
            404,
            "Unknown client [{$client}]. If you just deployed, run: php artisan config:clear"
        );

        $sn = $config['device_sn'];

        $month = $this->month($request->query('month'));
        $pin   = $request->query('pin');

        $start = $month->copy()->startOfMonth();
        $end   = $month->copy()->endOfMonth()->endOfDay();

        // Everything is scoped to this client's device
        $names = MirakiUser::where('device_sn', $sn)
            ->orderBy('name')
            ->pluck('name', 'pin');

        $punches = Miraki::where('device_sn', $sn)
            ->whereBetween('punched_at', [$start, $end])
            ->when($pin, fn ($q) => $q->where('pin', $pin))
            ->orderBy('pin')
            ->orderBy('punched_at')
            ->get();

        return view('miraki.report', [
            'client'     => $client,
            'clientName' => $config['name'],
            'month'      => $month,
            'monthKey'   => $month->format('Y-m'),
            'pin'        => $pin,
            'people'     => $names,
            'rows'       => $this->buildRows($punches, $names),
        ]);
    }

    /**
     * One row per punch, in the order they happened.
     */
    private function buildRows($punches, $names): array {

        $mode = config('miraki.punch_state', 'auto');

        // 'auto': trust the device as soon as it actually starts sending a state.
        // While every punch arrives as 0 the device is clearly not telling us,
        // so fall back to counting. This switches over by itself the day the
        // punch state key is enabled — no setting to remember to change.
        if ($mode === 'auto') {
            $mode = $punches->contains(fn ($p) => $p->status > 0) ? 'device' : 'alternate';
        }

        $useDevice = $mode === 'device';

        // Counts punches per person per day, so 'alternate' knows the turn.
        $seen = [];

        $rows = [];

        foreach ($punches as $punch) {

            $date = $punch->punched_at->toDateString();
            $key  = $punch->pin . '|' . $date;

            $seen[$key] = ($seen[$key] ?? 0) + 1;

            $rows[] = [
                'name'      => $names[$punch->pin] ?? ('PIN ' . $punch->pin),
                'pin'       => $punch->pin,
                'date'      => $date,
                'day'       => $punch->punched_at->format('D'),
                'time'      => $punch->punched_at->format('H:i'),
                'direction' => $useDevice
                    ? (self::DEVICE_STATES[$punch->status] ?? 'IN')
                    : ($seen[$key] % 2 === 1 ? 'IN' : 'OUT'),
                'sortKey'   => $punch->punched_at->format('Y-m-d H:i:s'),
            ];
        }

        // Newest punch first. The IN/OUT counting above already ran in
        // chronological order, so flipping the display order is safe.
        usort($rows, fn ($a, $b) => [$b['sortKey'], $a['name']] <=> [$a['sortKey'], $b['name']]);

        return $rows;
    }

    private function month(?string $value): Carbon {

        try {
            return Carbon::createFromFormat('Y-m', (string) $value)->startOfMonth();
        } catch (\Throwable $e) {
            return Carbon::now()->startOfMonth();
        }
    }

}
