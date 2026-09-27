<?php

namespace Database\Seeders;

use App\Models\AttendanceDevice;
use App\Models\Branch;
use App\Models\Employer;
use App\Models\Holiday;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceSeeder extends Seeder
{
    /** How far back the punch history goes. */
    private const DAYS_OF_HISTORY = 60;

    /**
     * Demo punches start their serial numbers up here so they never collide with a
     * real device's own serialNo, which starts low and counts up. A collision would
     * make EventController's idempotency guard silently drop genuine punches.
     */
    private const SERIAL_BASE = 900000;

    public function run(): void
    {
        $devices = $this->seedDevices();
        $this->seedEvents($devices);
    }

    /** @return array<int, int> branch id => device id */
    private function seedDevices(): array
    {
        $vendors = [
            'Erbil Head Office' => ['vendor' => 'ZKTeco', 'name' => 'Main Entrance - SpeedFace V5L', 'ip' => '192.168.1.201', 'mac' => 'a4:d5:c2:62:3e:31'],
            'Sulaymaniyah Branch' => ['vendor' => 'ZKTeco', 'name' => 'Reception - MB460', 'ip' => '192.168.2.201', 'mac' => 'a4:d5:c2:62:3e:32'],
            'Duhok Branch' => ['vendor' => 'Hikvision', 'name' => 'Warehouse Gate - DS-K1T341', 'ip' => '192.168.3.201', 'mac' => 'a4:d5:c2:62:3e:33'],
            'Baghdad Branch' => ['vendor' => 'Hikvision', 'name' => 'Lobby Terminal - DS-K1T671', 'ip' => '192.168.4.201', 'mac' => 'a4:d5:c2:62:3e:34'],
        ];

        $map = [];

        foreach (Branch::all() as $branch) {
            $config = $vendors[$branch->getTranslation('name', 'en')] ?? $vendors['Erbil Head Office'];

            $device = AttendanceDevice::create([
                'branch_id' => $branch->id,
                'vendor' => $config['vendor'],
                'name' => $config['name'],
                'ip_address' => $config['ip'],
                'port' => $config['vendor'] === 'Hikvision' ? 443 : 4370,
                'mac_address' => $config['mac'],
            ]);

            $map[$branch->id] = $device->id;
        }

        return $map;
    }

    /** @param array<int, int> $devices */
    private function seedEvents(array $devices): void
    {
        $holidaysByBranch = Holiday::where('is_working_day_override', false)
            ->get()
            ->groupBy('branch_id')
            ->map(fn ($rows) => $rows->pluck('date')->map(fn ($d) => $d->format('Y-m-d'))->all())
            ->all();

        $employers = Employer::with('employerShifts.shift')->get();
        $serials = [];
        $rows = [];
        $today = Carbon::today();

        foreach ($employers as $index => $employer) {
            $shift = $employer->employerShifts->first()?->shift;

            if (! $shift) {
                continue;
            }

            $deviceId = $devices[$employer->branch_id] ?? null;
            $holidays = $holidaysByBranch[$employer->branch_id] ?? [];
            $code = 'EMP-'.str_pad((string) $employer->id, 4, '0', STR_PAD_LEFT);

            for ($back = self::DAYS_OF_HISTORY; $back >= 1; $back--) {
                $day = $today->copy()->subDays($back);

                if (! in_array($day->dayOfWeekIso, $shift->days_of_week, true)) {
                    continue;
                }

                if (in_array($day->format('Y-m-d'), $holidays, true)) {
                    continue;
                }

                if ($day->lt(Carbon::parse($employer->hire_date->format('Y-m-d')))) {
                    continue;
                }

                // A predictable sprinkle of absences, roughly one day a month each.
                $absenceSeed = ($employer->id * 7 + $back * 3) % 23;

                if ($absenceSeed === 0) {
                    continue;
                }

                [$startHour, $startMinute] = explode(':', substr((string) $shift->start_time, 0, 5));
                [$endHour, $endMinute] = explode(':', substr((string) $shift->end_time, 0, 5));

                $lateMinutes = match (($employer->id + $back) % 9) {
                    0 => 18,
                    1 => 7,
                    2 => 31,
                    default => -(($back % 6) + 1),
                };

                $overtimeMinutes = match (($employer->id + $back) % 7) {
                    0 => 65,
                    1 => 25,
                    default => ($back % 5) - 2,
                };

                $in = $day->copy()->setTime((int) $startHour, (int) $startMinute)->addMinutes($lateMinutes);
                $out = $day->copy()->setTime((int) $endHour, (int) $endMinute)->addMinutes($overtimeMinutes);

                // Night shift clocks out the next morning.
                if ($out->lte($in)) {
                    $out->addDay();
                }

                $source = ($employer->id + $back) % 17 === 0 ? 'MOBILE' : 'BIOMETRIC';

                $rows[] = $this->row($employer, $deviceId, $code, $serials, 'IN', $in, $source);
                $rows[] = $this->row($employer, $deviceId, $code, $serials, 'OUT', $out, $source);

                // Occasional double punch, flagged as invalid so the filter has data.
                if (($employer->id + $back) % 41 === 0) {
                    $rows[] = $this->row(
                        $employer,
                        $deviceId,
                        $code,
                        $serials,
                        'IN',
                        $in->copy()->addMinutes(2),
                        $source,
                        false,
                        'Duplicate punch within 2 minutes'
                    );
                }

                if (count($rows) >= 500) {
                    DB::table('attendance_events')->insert($rows);
                    $rows = [];
                }
            }
        }

        if ($rows !== []) {
            DB::table('attendance_events')->insert($rows);
        }
    }

    /** @param array<int, int> $serials */
    private function row(
        Employer $employer,
        ?int $deviceId,
        string $code,
        array &$serials,
        string $type,
        Carbon $at,
        string $source,
        bool $isValid = true,
        ?string $invalidReason = null,
    ): array {
        $serials[$deviceId] = ($serials[$deviceId] ?? self::SERIAL_BASE) + 1;

        return [
            'branch_id' => $employer->branch_id,
            'employer_id' => $employer->id,
            'device_id' => $deviceId,
            'device_user_code' => $code,
            'device_serial_no' => $serials[$deviceId],
            'source' => $source,
            'event_type' => $type,
            'event_at' => $at->format('Y-m-d H:i:s'),
            'selfie_path' => null,
            'is_valid' => $isValid,
            'invalid_reason' => $invalidReason,
            'created_by' => null,
            'updated_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
