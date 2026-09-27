<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Holiday;
use Database\Seeders\Support\Demo;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        // Public holidays as observed in Iraq and the Kurdistan Region.
        // Religious dates move with the lunar calendar; these are the 2026 observances.
        $holidays = [
            ['date' => '2026-01-01', 'name' => Demo::t("New Year's Day", 'سەری ساڵی نوێ', 'رأس السنة الميلادية')],
            ['date' => '2026-03-05', 'name' => Demo::t('Kurdish Uprising Day', 'ڕۆژی ڕاپەرین', 'يوم الانتفاضة الكوردية')],
            ['date' => '2026-03-16', 'name' => Demo::t('Halabja Memorial Day', 'ڕۆژی یادی هەڵەبجە', 'يوم ذكرى حلبجة')],
            ['date' => '2026-03-19', 'name' => Demo::t('Eid al-Fitr (Day 1)', 'جەژنی ڕەمەزان (ڕۆژی یەکەم)', 'عيد الفطر (اليوم الأول)')],
            ['date' => '2026-03-20', 'name' => Demo::t('Eid al-Fitr (Day 2)', 'جەژنی ڕەمەزان (ڕۆژی دووەم)', 'عيد الفطر (اليوم الثاني)')],
            ['date' => '2026-03-21', 'name' => Demo::t('Nowruz - Kurdish New Year', 'نەورۆز - سەری ساڵی کوردی', 'نوروز - رأس السنة الكوردية')],
            ['date' => '2026-03-22', 'name' => Demo::t('Nowruz Holiday', 'پشووی نەورۆز', 'عطلة نوروز')],
            ['date' => '2026-05-01', 'name' => Demo::t('Labour Day', 'ڕۆژی کرێکاران', 'عيد العمال')],
            ['date' => '2026-05-27', 'name' => Demo::t('Eid al-Adha (Day 1)', 'جەژنی قوربان (ڕۆژی یەکەم)', 'عيد الأضحى (اليوم الأول)')],
            ['date' => '2026-05-28', 'name' => Demo::t('Eid al-Adha (Day 2)', 'جەژنی قوربان (ڕۆژی دووەم)', 'عيد الأضحى (اليوم الثاني)')],
            ['date' => '2026-05-29', 'name' => Demo::t('Eid al-Adha (Day 3)', 'جەژنی قوربان (ڕۆژی سێیەم)', 'عيد الأضحى (اليوم الثالث)')],
            ['date' => '2026-06-16', 'name' => Demo::t('Islamic New Year', 'سەری ساڵی کۆچی', 'رأس السنة الهجرية')],
            ['date' => '2026-06-25', 'name' => Demo::t('Ashura', 'عاشورا', 'عاشوراء')],
            ['date' => '2026-07-14', 'name' => Demo::t('Republic Day', 'ڕۆژی کۆماری', 'عيد الجمهورية')],
            ['date' => '2026-08-25', 'name' => Demo::t("Prophet Muhammad's Birthday", 'لەدایکبوونی پێغەمبەر', 'المولد النبوي الشريف')],
            ['date' => '2026-10-03', 'name' => Demo::t('Iraqi National Day', 'ڕۆژی نیشتمانی عێراق', 'اليوم الوطني العراقي')],
            ['date' => '2026-12-17', 'name' => Demo::t('Kurdistan Flag Day', 'ڕۆژی ئاڵای کوردستان', 'يوم علم كوردستان')],
            ['date' => '2026-12-25', 'name' => Demo::t('Christmas Day', 'جەژنی کریسمس', 'عيد الميلاد')],
            ['date' => '2027-01-01', 'name' => Demo::t("New Year's Day", 'سەری ساڵی نوێ', 'رأس السنة الميلادية')],
        ];

        foreach (Branch::all() as $branch) {
            foreach ($holidays as $holiday) {
                Holiday::create([
                    'branch_id' => $branch->id,
                    'date' => $holiday['date'],
                    'name' => $holiday['name'],
                    'is_working_day_override' => false,
                ]);
            }

            // One Friday turned into a working day, to show the override flag in use.
            Holiday::create([
                'branch_id' => $branch->id,
                'date' => '2026-11-06',
                'name' => Demo::t('Annual Stock Count (working day)', 'ژماردنی ساڵانەی کۆگا (ڕۆژی کار)', 'الجرد السنوي (يوم عمل)'),
                'is_working_day_override' => true,
            ]);
        }
    }
}
