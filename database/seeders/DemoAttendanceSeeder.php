<?php

namespace Database\Seeders;

use App\Support\MerakiSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A month of realistic, messy attendance, written to the REAL Meraki device
 * so it shows up on /meraki/report next to the real punches.
 *
 *   php artisan db:seed --class=DemoAttendanceSeeder
 *
 * Two rules keep the real data safe:
 *
 *   1. Test people use PINs 101-108. The real staff are PIN 1 and 2, so their
 *      names are never overwritten.
 *   2. Every row is stamped raw = 'seeded'. Wiping deletes only rows with that
 *      stamp, so a real punch can never be caught by it.
 *
 * Run it as many times as you like — it clears its own rows first.
 * To remove it:  DemoAttendanceSeeder::wipe();
 */
class DemoAttendanceSeeder extends Seeder {

    /** How seeded rows are told apart from real ones. Never change this. */
    public const STAMP = 'seeded';

    private const IN  = 0;
    private const OUT = 1;

    /** pin => name. High PINs on purpose, so real staff are untouched. */
    private const PEOPLE = [
        101 => 'Ahmed Ali',
        102 => 'Sara Hassan',
        103 => 'Zaid Omar',
        104 => 'Nour Kareem',
        105 => 'Ali Salim',
        106 => 'Hussein Jabar',
        107 => 'Rana Faiz',
        108 => 'Omar Adnan',
    ];

    /** The real device, from config/meraki.php. */
    public static function sn(): string {

        return (string) config('meraki.clients.meraki.device_sn');
    }

    public function run(): void {

        if (self::sn() === '') {
            $this->command?->warn('No device serial in config/meraki.php. Set MERAKI_DEVICE_SN in .env.');

            return;
        }

        if (! self::ready()) {
            $this->command?->warn('Attendance tables are missing. Run php artisan migrate first.');

            return;
        }

        self::wipe();

        foreach (self::PEOPLE as $pin => $name) {
            DB::table('meraki_users')->insert([
                'device_sn'  => self::sn(),
                'pin'        => (string) $pin,
                'name'       => $name,
                'privilege'  => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $rows  = [];
        $start = Carbon::now()->startOfMonth();
        $last  = Carbon::now()->endOfMonth()->min(Carbon::now())->startOfDay();

        for ($day = $start->copy(); $day <= $last; $day->addDay()) {

            $date  = $day->toDateString();
            $dayNo = $day->day;

            // Friday is closed, except one where Omar comes in alone.
            if ($day->dayOfWeek === Carbon::FRIDAY) {
                if ($dayNo > 7 && $dayNo < 15) {
                    $this->pair($rows, 108, $date, '09:12', '13:40');
                }

                continue;
            }

            // 1 — steady, boring, correct
            $this->pair($rows, 101, $date, '07:58', '17:03');

            // 2 — early and late, so she builds up extra time
            $this->pair($rows, 102, $date, '07:05', $dayNo % 3 === 0 ? '19:30' : '17:45');

            // 3 — short days, builds up short time
            $this->pair($rows, 103, $date, '09:20', '15:10');

            // 4 — forgets to check out roughly once a week
            if ($dayNo % 7 === 3) {
                $rows[] = $this->row(104, "$date 08:05:00", self::IN);
            } else {
                $this->pair($rows, 104, $date, '08:05', '17:00');
            }

            // 5 — presses the finger two or three times every single time
            $rows[] = $this->row(105, "$date 08:00:00", self::IN);
            $rows[] = $this->row(105, "$date 08:00:04", self::IN);
            $rows[] = $this->row(105, "$date 17:02:00", self::OUT);
            $rows[] = $this->row(105, "$date 17:02:03", self::OUT);
            $rows[] = $this->row(105, "$date 17:02:07", self::OUT);

            // 6 — goes out for lunch and back, so worked is less than in-to-out
            $this->pair($rows, 106, $date, '08:10', '12:30');
            $this->pair($rows, 106, $date, '13:30', '17:15');

            // 7 — new hire, starts on the 10th
            if ($dayNo >= 10) {
                $this->pair($rows, 107, $date, '08:30', '17:30');
            }

            // 8 — the device only started sending real punch states on the 12th
            if ($dayNo < 12) {
                $rows[] = $this->row(108, "$date 08:15:00", self::IN);
                $rows[] = $this->row(108, "$date 16:45:00", self::IN);
            } else {
                $this->pair($rows, 108, $date, '08:15', '16:45');
            }
        }

        // one day the whole place was shut mid week
        $shut = $start->copy()->addDays(19)->toDateString();

        $rows = array_values(array_filter(
            $rows,
            fn ($r) => ! str_starts_with($r['punched_at'], $shut)
        ));

        foreach (array_chunk($rows, 400) as $chunk) {
            DB::table('meraki')->insert($chunk);
        }

        $this->command?->info(sprintf(
            'Seeded %d punches for %d test people (PIN 101-108). Open /meraki/report',
            count($rows),
            count(self::PEOPLE)
        ));
    }

    /**
     * Delete only the seeded rows. Real punches carry the device's own text
     * in `raw`, never the stamp, so they cannot be caught by this.
     */
    public static function wipe(): void {

        if (! self::ready()) {
            return;
        }

        DB::table('meraki')
            ->where('device_sn', self::sn())
            ->where('raw', self::STAMP)
            ->delete();

        DB::table('meraki_users')
            ->where('device_sn', self::sn())
            ->whereIn('pin', array_map('strval', array_keys(self::PEOPLE)))
            ->delete();
    }

    public static function ready(): bool {

        return Schema::hasTable('meraki') && Schema::hasTable('meraki_users');
    }

    /** A check in and its check out. */
    private function pair(array &$rows, int $pin, string $date, string $in, string $out): void {

        $rows[] = $this->row($pin, "$date $in:00", self::IN);
        $rows[] = $this->row($pin, "$date $out:00", self::OUT);
    }

    private function row(int $pin, string $at, int $status): array {

        return [
            'device_sn'  => self::sn(),
            'pin'        => (string) $pin,
            'punched_at' => $at,
            'status'     => $status,
            'verify'     => 1,
            'raw'        => self::STAMP,
            // Same stamp the device puts on a real punch, so the seeded month
            // behaves exactly like a recorded one.
            'shift_id'   => MerakiSettings::shiftFor('meraki', (string) $pin)['id'],
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

}
