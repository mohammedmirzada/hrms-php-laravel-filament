<?php

namespace Database\Seeders;

use App\Models\SalaryStructure;
use Database\Seeders\Support\Demo;
use Illuminate\Database\Seeder;

class SalaryStructureSeeder extends Seeder
{
    public function run(): void
    {
        $structures = [
            [
                'name' => 'Standard Staff (USD)',
                'default_currency_code' => 'USD',
                'items' => [
                    ['name' => Demo::t('Housing Allowance', 'بەخشینی نیشتەجێبوون', 'بدل السكن'), 'type' => 'earning', 'calculation_type' => 'percentage', 'value' => 15.00],
                    ['name' => Demo::t('Transport Allowance', 'بەخشینی گواستنەوە', 'بدل النقل'), 'type' => 'earning', 'calculation_type' => 'fixed', 'value' => 100.00],
                    ['name' => Demo::t('Phone Allowance', 'بەخشینی تەلەفۆن', 'بدل الهاتف'), 'type' => 'earning', 'calculation_type' => 'fixed', 'value' => 25.00],
                    ['name' => Demo::t('Social Security (Employee)', 'دڵنیایی کۆمەڵایەتی (کارمەند)', 'الضمان الاجتماعي (الموظف)'), 'type' => 'deduction', 'calculation_type' => 'percentage', 'value' => 5.00],
                ],
            ],
            [
                'name' => 'Management (USD)',
                'default_currency_code' => 'USD',
                'items' => [
                    ['name' => Demo::t('Housing Allowance', 'بەخشینی نیشتەجێبوون', 'بدل السكن'), 'type' => 'earning', 'calculation_type' => 'percentage', 'value' => 25.00],
                    ['name' => Demo::t('Car Allowance', 'بەخشینی ئۆتۆمبێل', 'بدل السيارة'), 'type' => 'earning', 'calculation_type' => 'fixed', 'value' => 400.00],
                    ['name' => Demo::t('Management Bonus', 'پاداشتی بەڕێوەبردن', 'مكافأة إدارية'), 'type' => 'earning', 'calculation_type' => 'percentage', 'value' => 10.00],
                    ['name' => Demo::t('Social Security (Employee)', 'دڵنیایی کۆمەڵایەتی (کارمەند)', 'الضمان الاجتماعي (الموظف)'), 'type' => 'deduction', 'calculation_type' => 'percentage', 'value' => 5.00],
                ],
            ],
            [
                'name' => 'Field Staff (IQD)',
                'default_currency_code' => 'IQD',
                'items' => [
                    ['name' => Demo::t('Meal Allowance', 'بەخشینی خواردن', 'بدل الطعام'), 'type' => 'earning', 'calculation_type' => 'fixed', 'value' => 150000.00],
                    ['name' => Demo::t('Site Allowance', 'بەخشینی شوێنی کار', 'بدل الموقع'), 'type' => 'earning', 'calculation_type' => 'percentage', 'value' => 10.00],
                    ['name' => Demo::t('Social Security (Employee)', 'دڵنیایی کۆمەڵایەتی (کارمەند)', 'الضمان الاجتماعي (الموظف)'), 'type' => 'deduction', 'calculation_type' => 'percentage', 'value' => 5.00],
                    ['name' => Demo::t('Advance Repayment', 'گەڕاندنەوەی پێشەکی', 'استرداد السلفة'), 'type' => 'deduction', 'calculation_type' => 'fixed', 'value' => 50000.00],
                ],
            ],
        ];

        foreach ($structures as $data) {
            $items = $data['items'];
            unset($data['items']);

            $structure = SalaryStructure::create($data + ['is_active' => true]);

            foreach ($items as $item) {
                $structure->items()->create($item);
            }
        }
    }
}
