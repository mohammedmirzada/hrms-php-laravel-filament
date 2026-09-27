<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\EmploymentStatus;
use App\Models\Position;
use Database\Seeders\Support\Demo;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            [
                'name' => Demo::t('Erbil Head Office', 'نووسینگەی سەرەکی هەولێر', 'المكتب الرئيسي أربيل'),
                'address' => Demo::t('60m Street, Erbil, Kurdistan Region', 'شەقامی ٦٠ مەتری، هەولێر، هەرێمی کوردستان', 'شارع ٦٠ متر، أربيل، إقليم كوردستان'),
            ],
            [
                'name' => Demo::t('Sulaymaniyah Branch', 'لقی سلێمانی', 'فرع السليمانية'),
                'address' => Demo::t('Salim Street, Sulaymaniyah', 'شەقامی سالم، سلێمانی', 'شارع سالم، السليمانية'),
            ],
            [
                'name' => Demo::t('Duhok Branch', 'لقی دهۆک', 'فرع دهوك'),
                'address' => Demo::t('Nakhoshkhana Road, Duhok', 'ڕێگای نەخۆشخانە، دهۆک', 'طريق المستشفى، دهوك'),
            ],
            [
                'name' => Demo::t('Baghdad Branch', 'لقی بەغدا', 'فرع بغداد'),
                'address' => Demo::t('Karrada, Baghdad', 'کەڕادە، بەغدا', 'الكرادة، بغداد'),
            ],
        ];

        foreach ($branches as $branch) {
            Branch::create($branch);
        }

        $departments = [
            Demo::t('Human Resources', 'سەرچاوەی مرۆیی', 'الموارد البشرية'),
            Demo::t('Finance & Accounting', 'دارایی و ژمێریاری', 'المالية والمحاسبة'),
            Demo::t('Information Technology', 'تەکنەلۆژیای زانیاری', 'تكنولوجيا المعلومات'),
            Demo::t('Operations', 'کاروبارەکان', 'العمليات'),
            Demo::t('Sales & Marketing', 'فرۆشتن و بازاڕکردن', 'المبيعات والتسويق'),
            Demo::t('Logistics & Warehouse', 'گواستنەوە و کۆگا', 'الخدمات اللوجستية والمخازن'),
            Demo::t('Legal Affairs', 'کاروباری یاسایی', 'الشؤون القانونية'),
            Demo::t('Customer Support', 'پشتگیری کڕیار', 'دعم العملاء'),
        ];

        foreach ($departments as $name) {
            Department::create(['name' => $name]);
        }

        $positions = [
            Demo::t('General Manager', 'بەڕێوەبەری گشتی', 'المدير العام'),
            Demo::t('HR Manager', 'بەڕێوەبەری سەرچاوەی مرۆیی', 'مدير الموارد البشرية'),
            Demo::t('HR Officer', 'کارگێری سەرچاوەی مرۆیی', 'موظف موارد بشرية'),
            Demo::t('Chief Accountant', 'سەرۆک ژمێریار', 'رئيس الحسابات'),
            Demo::t('Accountant', 'ژمێریار', 'محاسب'),
            Demo::t('IT Manager', 'بەڕێوەبەری تەکنەلۆژیا', 'مدير تكنولوجيا المعلومات'),
            Demo::t('Software Engineer', 'ئەندازیاری سۆفتوێر', 'مهندس برمجيات'),
            Demo::t('Network Administrator', 'بەڕێوەبەری تۆڕ', 'مسؤول الشبكات'),
            Demo::t('Operations Manager', 'بەڕێوەبەری کاروبارەکان', 'مدير العمليات'),
            Demo::t('Sales Representative', 'نوێنەری فرۆشتن', 'مندوب مبيعات'),
            Demo::t('Marketing Specialist', 'پسپۆری بازاڕکردن', 'أخصائي تسويق'),
            Demo::t('Warehouse Supervisor', 'سەرپەرشتیاری کۆگا', 'مشرف المخزن'),
            Demo::t('Legal Advisor', 'ڕاوێژکاری یاسایی', 'مستشار قانوني'),
            Demo::t('Customer Support Agent', 'کارمەندی پشتگیری کڕیار', 'موظف دعم العملاء'),
            Demo::t('Driver', 'شۆفێر', 'سائق'),
        ];

        foreach ($positions as $name) {
            Position::create(['name' => $name]);
        }

        // Codes must match the badge colours in EmployerResource::table(),
        // which treats employment status as a lifecycle state.
        $statuses = [
            ['code' => 'active', 'name' => Demo::t('Active', 'چالاک', 'نشط')],
            ['code' => 'suspended', 'name' => Demo::t('Suspended', 'هەڵپەسێردراو', 'موقوف')],
            ['code' => 'resigned', 'name' => Demo::t('Resigned', 'دەستلەکارکێشانەوە', 'مستقيل')],
            ['code' => 'terminated', 'name' => Demo::t('Terminated', 'دەرکراو', 'منتهية خدمته')],
            ['code' => 'future_hired', 'name' => Demo::t('Future Hired', 'دامەزراندنی داهاتوو', 'تعيين مستقبلي')],
        ];

        foreach ($statuses as $status) {
            EmploymentStatus::create($status);
        }
    }
}
