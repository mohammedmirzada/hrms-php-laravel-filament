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
 * The device never says in or out, so punches of one day are paired by time:
 * 1st = IN, 2nd = OUT, 3rd = IN, 4th = OUT...  Worked time is the sum of the
 * pairs, so lunch breaks are excluded. An odd number of punches means someone
 * forgot to punch out — that day is flagged instead of guessed.
 *
 * Plain Blade page, no Filament.
 */
class MirakiReportController extends Controller {

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

        $rows = $this->buildRows($punches, $names);

        return view('miraki.report', [
            'client'     => $client,
            'clientName' => $config['name'],
            'month'      => $month,
            'monthKey'   => $month->format('Y-m'),
            'pin'        => $pin,
            'people'     => $names,
            'rows'       => $rows,
            'totals'     => $this->totals($rows),
            'grandMins'  => array_sum(array_column($rows, 'minutes')),
        ]);
    }

    /**
     * Group punches by person + day, then pair them into in/out.
     */
    private function buildRows($punches, $names): array {

        $rows = [];

        $grouped = $punches->groupBy(
            fn ($punch) => $punch->pin . '|' . $punch->punched_at->toDateString()
        );

        foreach ($grouped as $key => $group) {

            [$pin, $date] = explode('|', $key);

            $times = $group->pluck('punched_at')->sort()->values();

            // Pair them up: 0-1, 2-3, 4-5...  A trailing odd punch is left out.
            $minutes = 0;
            $pairs   = [];

            for ($i = 0; $i + 1 < $times->count(); $i += 2) {
                $in  = $times[$i];
                $out = $times[$i + 1];

                $minutes += (int) abs($in->diffInMinutes($out));
                $pairs[] = $in->format('H:i') . ' - ' . $out->format('H:i');
            }

            $rows[] = [
                'pin'        => $pin,
                'name'       => $names[$pin] ?? '—',
                'date'       => $date,
                'day'        => Carbon::parse($date)->format('D'),
                'first'      => $times->first()->format('H:i'),
                'last'       => $times->count() > 1 ? $times->last()->format('H:i') : null,
                'minutes'    => $minutes,
                'count'      => $times->count(),
                'pairs'      => $pairs,
                'incomplete' => $times->count() % 2 !== 0,
            ];
        }

        usort($rows, fn ($a, $b) => [$a['name'], $a['date']] <=> [$b['name'], $b['date']]);

        return $rows;
    }

    /** Per person totals for the summary block. */
    private function totals(array $rows): array {

        $totals = [];

        foreach ($rows as $row) {

            $key = $row['pin'];

            $totals[$key] ??= [
                'pin'        => $row['pin'],
                'name'       => $row['name'],
                'days'       => 0,
                'minutes'    => 0,
                'incomplete' => 0,
            ];

            $totals[$key]['days']++;
            $totals[$key]['minutes'] += $row['minutes'];
            $totals[$key]['incomplete'] += $row['incomplete'] ? 1 : 0;
        }

        return array_values($totals);
    }

    private function month(?string $value): Carbon {

        try {
            return Carbon::createFromFormat('Y-m', (string) $value)->startOfMonth();
        } catch (\Throwable $e) {
            return Carbon::now()->startOfMonth();
        }
    }

}
