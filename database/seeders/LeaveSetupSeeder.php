<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use Database\Seeders\Support\Demo;
use Illuminate\Database\Seeder;

class LeaveSetupSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => Demo::t('Annual Leave', 'مۆڵەتی ساڵانە', 'الإجازة السنوية'),
                'description' => Demo::t('Paid yearly leave accrued monthly.', 'مۆڵەتی ساڵانەی پارەدار کە مانگانە کۆ دەبێتەوە.', 'إجازة سنوية مدفوعة تتراكم شهرياً.'),
                'is_system' => true,
                'is_paid' => true,
                'document_type' => null,
                'default_unit' => 'DAY',
                'policy' => ['rate' => 1.75, 'unit' => 'DAY_PER_MONTH', 'cap' => 21, 'carryover' => true, 'carryover_cap' => 7],
            ],
            [
                'name' => Demo::t('Sick Leave', 'مۆڵەتی نەخۆشی', 'الإجازة المرضية'),
                'description' => Demo::t('Paid leave for illness, medical note required after 2 days.', 'مۆڵەتی پارەدار بۆ نەخۆشی، دوای دوو ڕۆژ بەڵگەنامەی پزیشکی پێویستە.', 'إجازة مدفوعة للمرض، تقرير طبي مطلوب بعد يومين.'),
                'is_system' => true,
                'is_paid' => true,
                'document_type' => 'Medical Certificate',
                'default_unit' => 'DAY',
                'policy' => ['rate' => 1.00, 'unit' => 'DAY_PER_MONTH', 'cap' => 12, 'carryover' => false, 'carryover_cap' => null],
            ],
            [
                'name' => Demo::t('Unpaid Leave', 'مۆڵەتی بێ مووچە', 'إجازة بدون راتب'),
                'description' => Demo::t('Leave without pay, approved case by case.', 'مۆڵەت بەبێ مووچە، بە پێی هەر حاڵەتێک پەسەند دەکرێت.', 'إجازة بدون راتب، تُعتمد حسب كل حالة.'),
                'is_system' => false,
                'is_paid' => false,
                'document_type' => null,
                'default_unit' => 'DAY',
                'policy' => ['rate' => null, 'unit' => null, 'cap' => null, 'carryover' => false, 'carryover_cap' => null],
            ],
            [
                'name' => Demo::t('Maternity Leave', 'مۆڵەتی منداڵبوون', 'إجازة الأمومة'),
                'description' => Demo::t('Statutory paid maternity leave.', 'مۆڵەتی منداڵبوونی پارەداری یاسایی.', 'إجازة أمومة مدفوعة بحكم القانون.'),
                'is_system' => true,
                'is_paid' => true,
                'document_type' => 'Medical Certificate',
                'default_unit' => 'DAY',
                'policy' => ['rate' => null, 'unit' => null, 'cap' => 72, 'carryover' => false, 'carryover_cap' => null],
            ],
            [
                'name' => Demo::t('Bereavement Leave', 'مۆڵەتی پرسە', 'إجازة الوفاة'),
                'description' => Demo::t('Paid leave after the death of a close relative.', 'مۆڵەتی پارەدار دوای کۆچی دوایی خزمێکی نزیک.', 'إجازة مدفوعة بعد وفاة أحد الأقارب.'),
                'is_system' => false,
                'is_paid' => true,
                'document_type' => 'Death Certificate',
                'default_unit' => 'DAY',
                'policy' => ['rate' => null, 'unit' => null, 'cap' => 7, 'carryover' => false, 'carryover_cap' => null],
            ],
            [
                'name' => Demo::t('Marriage Leave', 'مۆڵەتی هاوسەرگیری', 'إجازة الزواج'),
                'description' => Demo::t('One-off paid leave for the employee\'s own marriage.', 'مۆڵەتی پارەداری یەک جارە بۆ هاوسەرگیری خودی کارمەند.', 'إجازة مدفوعة لمرة واحدة لزواج الموظف.'),
                'is_system' => false,
                'is_paid' => true,
                'document_type' => 'Marriage Certificate',
                'default_unit' => 'DAY',
                'policy' => ['rate' => null, 'unit' => null, 'cap' => 10, 'carryover' => false, 'carryover_cap' => null],
            ],
            [
                'name' => Demo::t('Hourly Permission', 'مۆڵەتی کاتژمێری', 'إجازة ساعية'),
                'description' => Demo::t('Short hourly absence during the working day.', 'دوورکەوتنەوەی کورتی کاتژمێری لە ماوەی ڕۆژی کاردا.', 'غياب ساعي قصير خلال يوم العمل.'),
                'is_system' => false,
                'is_paid' => true,
                'document_type' => null,
                'default_unit' => 'HOUR',
                'policy' => ['rate' => 4.00, 'unit' => 'HOUR_PER_MONTH', 'cap' => 48, 'carryover' => false, 'carryover_cap' => null],
            ],
        ];

        $branches = Branch::all();

        foreach ($types as $data) {
            $policy = $data['policy'];
            unset($data['policy']);

            $type = LeaveType::create($data);

            foreach ($branches as $branch) {
                LeavePolicy::create([
                    'branch_id' => $branch->id,
                    'leave_type_id' => $type->id,
                    'accrual_enabled' => (bool) $policy['rate'],
                    'accrual_rate' => $policy['rate'],
                    'accrual_unit' => $policy['unit'],
                    'accrual_start_rule' => $policy['rate'] ? 'AFTER_PROBATION' : null,
                    'accrual_start_month_day' => null,
                    'annual_cap' => $policy['cap'],
                    'carryover_enabled' => $policy['carryover'],
                    'carryover_cap' => $policy['carryover_cap'],
                    'carryover_expiry_date' => $policy['carryover'] ? '03-31' : null,
                    'allow_hourly' => $data['default_unit'] === 'HOUR',
                    'allow_half_day' => $data['default_unit'] === 'DAY',
                    'min_request_unit_minutes' => $data['default_unit'] === 'HOUR' ? 60 : 240,
                    'requires_manager_approval' => true,
                    'requires_hr_approval' => true,
                    'requires_final_approval' => ! $data['is_paid'] || $data['default_unit'] === 'DAY',
                ]);
            }
        }
    }
}
