<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Shift;
use Database\Seeders\Support\Demo;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        // days_of_week uses ISO day numbers (1 = Monday … 7 = Sunday).
        // Sunday–Thursday is the working week here, Friday and Saturday are off.
        $sunToThu = [7, 1, 2, 3, 4];
        $satToThu = [6, 7, 1, 2, 3, 4];

        foreach (Branch::all() as $branch) {
            Shift::create([
                'branch_id' => $branch->id,
                'code' => 'MORNING',
                'name' => Demo::t('Morning Shift', 'شیفتی بەیانی', 'الوردية الصباحية'),
                'start_time' => '08:30',
                'end_time' => '16:30',
                'days_of_week' => $sunToThu,
            ]);

            Shift::create([
                'branch_id' => $branch->id,
                'code' => 'AFTERNOON',
                'name' => Demo::t('Afternoon Shift', 'شیفتی نیوەڕۆ', 'الوردية المسائية'),
                'start_time' => '14:00',
                'end_time' => '22:00',
                'days_of_week' => $satToThu,
            ]);

            Shift::create([
                'branch_id' => $branch->id,
                'code' => 'NIGHT',
                'name' => Demo::t('Night Shift', 'شیفتی شەو', 'الوردية الليلية'),
                'start_time' => '22:00',
                'end_time' => '06:00',
                'days_of_week' => $satToThu,
            ]);
        }
    }
}
