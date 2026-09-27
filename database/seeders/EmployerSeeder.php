<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employer;
use App\Models\EmploymentStatus;
use App\Models\Position;
use App\Models\SalaryStructure;
use App\Models\Shift;
use Database\Seeders\Support\Demo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class EmployerSeeder extends Seeder
{
    /** @var array<string, int> */
    private array $departments = [];

    /** @var array<string, int> */
    private array $positions = [];

    /** @var array<string, int> */
    private array $branches = [];

    /** @var array<string, int> */
    private array $statuses = [];

    /** @var array<string, int> */
    private array $structures = [];

    public function run(): void
    {
        $this->buildLookups();

        $roster = $this->roster();
        $created = [];

        // First pass: create everyone without a manager, so any reporting line resolves.
        foreach ($roster as $key => $person) {
            $created[$key] = $this->createEmployer($key, $person);
        }

        // Second pass: wire the reporting lines.
        foreach ($roster as $key => $person) {
            if (! empty($person['manager']) && isset($created[$person['manager']])) {
                $created[$key]->update(['manager_id' => $created[$person['manager']]->id]);
            }
        }
    }

    private function buildLookups(): void
    {
        foreach (Department::all() as $row) {
            $this->departments[$row->getTranslation('name', 'en')] = $row->id;
        }

        foreach (Position::all() as $row) {
            $this->positions[$row->getTranslation('name', 'en')] = $row->id;
        }

        foreach (Branch::all() as $row) {
            $this->branches[$row->getTranslation('name', 'en')] = $row->id;
        }

        foreach (EmploymentStatus::all() as $row) {
            $this->statuses[$row->code] = $row->id;
        }

        foreach (SalaryStructure::all() as $row) {
            $this->structures[$row->name] = $row->id;
        }
    }

    private function createEmployer(int $key, array $person): Employer
    {
        $hireDate = Carbon::parse($person['hired']);
        $status = $person['status'] ?? 'FT';

        $employer = Employer::create([
            'full_name' => Demo::t($person['en'], $person['ckb'], $person['ar']),
            'genre' => $person['gender'],
            'email' => $person['email'],
            'password' => 'password',
            'phone_number_1' => $person['phone'],
            'phone_number_2' => null,
            'date_of_birth' => $person['dob'],
            'marital_status' => $person['marital'],
            'emergency_contact' => [[
                'name' => $person['contact_name'],
                'phone_code' => '+964',
                'phone' => $person['contact_phone'],
                'relation' => $person['contact_relation'],
            ]],
            'department_id' => $this->departments[$person['department']],
            'position_id' => $this->positions[$person['position']],
            'branch_id' => $this->branches[$person['branch']],
            'hire_date' => $hireDate,
            'probation_period_start_date' => $hireDate,
            'probation_period_end_date' => $hireDate->copy()->addMonths(3),
            'contract_expiry_date' => $person['contract_expiry'] ?? null,
            'employment_status_id' => $this->statuses[$status],
        ]);

        // Pay: effective from the hire date, or from the start of this year for older staff.
        $effectiveFrom = $hireDate->lt(now()->startOfYear()) ? now()->startOfYear() : $hireDate;

        $employer->compensations()->create([
            'salary_structure_id' => $this->structures[$person['structure']],
            'currency_code' => str_contains($person['structure'], 'IQD') ? 'IQD' : 'USD',
            'basic_salary' => $person['salary'],
            'effective_from' => $effectiveFrom,
            'effective_to' => null,
        ]);

        // Shift assignment for the employee's own branch.
        $shift = Shift::where('branch_id', $employer->branch_id)
            ->where('code', $person['shift'] ?? 'MORNING')
            ->first();

        if ($shift) {
            $employer->employerShifts()->create([
                'shift_id' => $shift->id,
                'effective_from' => $effectiveFrom,
                'effective_to' => null,
            ]);
        }

        $this->createDocuments($employer, $key);

        return $employer;
    }

    private function createDocuments(Employer $employer, int $key): void
    {
        $plan = [
            ['type' => 'ID Card', 'expiry' => now()->addMonths(18 + ($key % 12))],
            ['type' => 'Contract', 'expiry' => now()->addMonths(6 + ($key % 9))],
        ];

        // A few documents that are already expired or about to expire, so the
        // expiry report and the dashboard widget have something to show.
        if ($key % 5 === 0) {
            $plan[] = ['type' => 'Passport', 'expiry' => now()->subDays(10 + $key)];
        }

        if ($key % 7 === 0) {
            $plan[] = ['type' => 'Work Permit', 'expiry' => now()->addDays(12 + $key)];
        }

        foreach ($plan as $index => $document) {
            $slug = strtolower(str_replace(' ', '-', $document['type']));
            $path = "documents/demo-{$employer->id}-{$slug}.pdf";

            $employer->documents()->create([
                'document_type' => $document['type'],
                'file_path' => $path,
                'expiry_date' => $document['expiry'],
            ]);

            $this->writePlaceholderPdf($path, $employer->getTranslation('full_name', 'en'), $document['type']);
        }
    }

    /** Write a tiny one-page PDF so document links in the panel actually open. */
    private function writePlaceholderPdf(string $path, string $name, string $type): void
    {
        $full = storage_path('app/public/'.$path);

        if (! is_dir(dirname($full))) {
            mkdir(dirname($full), 0755, true);
        }

        $text = "{$type} - {$name}";
        $content = 'BT /F1 18 Tf 60 720 Td ('.str_replace(['(', ')'], '', $text).') Tj ET';

        $objects = [
            "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n",
            "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n",
            "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj\n",
            "4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n",
            '5 0 obj << /Length '.strlen($content)." >> stream\n{$content}\nendstream endobj\n",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xrefStart = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= 'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xrefStart}\n%%EOF";

        file_put_contents($full, $pdf);
    }

    /**
     * The demo roster: Kurdish and Arabic staff, each name written in English,
     * Sorani Kurdish and Arabic so the language switcher has something to show.
     */
    private function roster(): array
    {
        return [
            1 => [
                'en' => 'Aram Kamal', 'ckb' => 'ئارام کەمال', 'ar' => 'آرام كمال',
                'gender' => 'male', 'email' => 'aram.kamal@hrms.test', 'phone' => '+9647501000001',
                'dob' => '1979-04-12', 'marital' => 'married',
                'contact_name' => 'Shukria Kamal', 'contact_phone' => '7701000001', 'contact_relation' => 'spouse',
                'department' => 'Operations', 'position' => 'General Manager', 'branch' => 'Erbil Head Office',
                'hired' => '2019-01-15', 'status' => 'active', 'structure' => 'Management (USD)', 'salary' => 4500,
                'shift' => 'MORNING', 'manager' => null,
            ],
            2 => [
                'en' => 'Shirin Osman', 'ckb' => 'شیرین عوسمان', 'ar' => 'شيرين عثمان',
                'gender' => 'female', 'email' => 'shirin.osman@hrms.test', 'phone' => '+9647501000002',
                'dob' => '1985-09-03', 'marital' => 'married',
                'contact_name' => 'Osman Salih', 'contact_phone' => '7701000002', 'contact_relation' => 'spouse',
                'department' => 'Human Resources', 'position' => 'HR Manager', 'branch' => 'Erbil Head Office',
                'hired' => '2019-06-01', 'status' => 'active', 'structure' => 'Management (USD)', 'salary' => 2600,
                'shift' => 'MORNING', 'manager' => 1,
            ],
            3 => [
                'en' => 'Karwan Ahmed', 'ckb' => 'کاروان ئەحمەد', 'ar' => 'كاروان أحمد',
                'gender' => 'male', 'email' => 'karwan.ahmed@hrms.test', 'phone' => '+9647501000003',
                'dob' => '1982-02-20', 'marital' => 'married',
                'contact_name' => 'Nasrin Ahmed', 'contact_phone' => '7701000003', 'contact_relation' => 'spouse',
                'department' => 'Finance & Accounting', 'position' => 'Chief Accountant', 'branch' => 'Erbil Head Office',
                'hired' => '2019-03-10', 'status' => 'active', 'structure' => 'Management (USD)', 'salary' => 2800,
                'shift' => 'MORNING', 'manager' => 1,
            ],
            4 => [
                'en' => 'Zana Tofiq', 'ckb' => 'زانا تۆفیق', 'ar' => 'زانا توفيق',
                'gender' => 'male', 'email' => 'zana.tofiq@hrms.test', 'phone' => '+9647501000004',
                'dob' => '1988-11-07', 'marital' => 'married',
                'contact_name' => 'Roj Tofiq', 'contact_phone' => '7701000004', 'contact_relation' => 'sibling',
                'department' => 'Information Technology', 'position' => 'IT Manager', 'branch' => 'Erbil Head Office',
                'hired' => '2020-02-01', 'status' => 'active', 'structure' => 'Management (USD)', 'salary' => 3000,
                'shift' => 'MORNING', 'manager' => 1,
            ],
            5 => [
                'en' => 'Mustafa Al-Kadhimi', 'ckb' => 'مستەفا ئەلکازیمی', 'ar' => 'مصطفى الكاظمي',
                'gender' => 'male', 'email' => 'mustafa.kadhimi@hrms.test', 'phone' => '+9647801000005',
                'dob' => '1981-07-19', 'marital' => 'married',
                'contact_name' => 'Rana Al-Kadhimi', 'contact_phone' => '7801000005', 'contact_relation' => 'spouse',
                'department' => 'Operations', 'position' => 'Operations Manager', 'branch' => 'Baghdad Branch',
                'hired' => '2020-05-17', 'status' => 'active', 'structure' => 'Management (USD)', 'salary' => 2700,
                'shift' => 'MORNING', 'manager' => 1,
            ],
            6 => [
                'en' => 'Nazdar Yousif', 'ckb' => 'نازدار یوسف', 'ar' => 'نازدار يوسف',
                'gender' => 'female', 'email' => 'nazdar.yousif@hrms.test', 'phone' => '+9647501000006',
                'dob' => '1991-03-25', 'marital' => 'single',
                'contact_name' => 'Yousif Hama', 'contact_phone' => '7701000006', 'contact_relation' => 'parent',
                'department' => 'Sales & Marketing', 'position' => 'Marketing Specialist', 'branch' => 'Erbil Head Office',
                'hired' => '2021-04-04', 'status' => 'active', 'structure' => 'Standard Staff (USD)', 'salary' => 1500,
                'shift' => 'MORNING', 'manager' => 1,
            ],
            7 => [
                'en' => 'Bahoz Rashid', 'ckb' => 'بەهۆز رەشید', 'ar' => 'بهوز رشيد',
                'gender' => 'male', 'email' => 'bahoz.rashid@hrms.test', 'phone' => '+9647501000007',
                'dob' => '1986-12-01', 'marital' => 'married',
                'contact_name' => 'Gulala Rashid', 'contact_phone' => '7701000007', 'contact_relation' => 'spouse',
                'department' => 'Logistics & Warehouse', 'position' => 'Warehouse Supervisor', 'branch' => 'Duhok Branch',
                'hired' => '2020-09-13', 'status' => 'active', 'structure' => 'Field Staff (IQD)', 'salary' => 1400000,
                'shift' => 'AFTERNOON', 'manager' => 1,
            ],
            8 => [
                'en' => 'Berivan Latif', 'ckb' => 'بەریڤان لەتیف', 'ar' => 'بيريفان لطيف',
                'gender' => 'female', 'email' => 'berivan.latif@hrms.test', 'phone' => '+9647501000008',
                'dob' => '1987-05-30', 'marital' => 'divorced',
                'contact_name' => 'Latif Kareem', 'contact_phone' => '7701000008', 'contact_relation' => 'parent',
                'department' => 'Legal Affairs', 'position' => 'Legal Advisor', 'branch' => 'Erbil Head Office',
                'hired' => '2021-01-11', 'status' => 'active', 'structure' => 'Management (USD)', 'salary' => 2200,
                'shift' => 'MORNING', 'manager' => 1,
            ],
            9 => [
                'en' => 'Layla Hussein', 'ckb' => 'لەیلا حوسێن', 'ar' => 'ليلى حسين',
                'gender' => 'female', 'email' => 'layla.hussein@hrms.test', 'phone' => '+9647701000009',
                'dob' => '1992-08-14', 'marital' => 'married',
                'contact_name' => 'Hussein Ali', 'contact_phone' => '7711000009', 'contact_relation' => 'spouse',
                'department' => 'Customer Support', 'position' => 'Customer Support Agent', 'branch' => 'Sulaymaniyah Branch',
                'hired' => '2021-08-22', 'status' => 'active', 'structure' => 'Standard Staff (USD)', 'salary' => 1100,
                'shift' => 'MORNING', 'manager' => 1,
            ],
            10 => [
                'en' => 'Lana Bakhtiar', 'ckb' => 'لانە بەختیار', 'ar' => 'لانة بختيار',
                'gender' => 'female', 'email' => 'lana.bakhtiar@hrms.test', 'phone' => '+9647501000010',
                'dob' => '1994-10-09', 'marital' => 'single',
                'contact_name' => 'Bakhtiar Sabir', 'contact_phone' => '7701000010', 'contact_relation' => 'parent',
                'department' => 'Human Resources', 'position' => 'HR Officer', 'branch' => 'Erbil Head Office',
                'hired' => '2022-02-14', 'status' => 'active', 'structure' => 'Standard Staff (USD)', 'salary' => 1200,
                'shift' => 'MORNING', 'manager' => 2,
            ],
            11 => [
                'en' => 'Huda Jassim', 'ckb' => 'هودا جاسم', 'ar' => 'هدى جاسم',
                'gender' => 'female', 'email' => 'huda.jassim@hrms.test', 'phone' => '+9647801000011',
                'dob' => '1993-06-18', 'marital' => 'married',
                'contact_name' => 'Jassim Hamid', 'contact_phone' => '7801000011', 'contact_relation' => 'parent',
                'department' => 'Human Resources', 'position' => 'HR Officer', 'branch' => 'Baghdad Branch',
                'hired' => '2022-07-03', 'status' => 'active', 'structure' => 'Standard Staff (USD)', 'salary' => 1150,
                'shift' => 'MORNING', 'manager' => 2,
            ],
            12 => [
                'en' => 'Hemin Salih', 'ckb' => 'هێمن ساڵح', 'ar' => 'هێمن صالح',
                'gender' => 'male', 'email' => 'hemin.salih@hrms.test', 'phone' => '+9647501000012',
                'dob' => '1990-01-27', 'marital' => 'married',
                'contact_name' => 'Awat Salih', 'contact_phone' => '7701000012', 'contact_relation' => 'sibling',
                'department' => 'Finance & Accounting', 'position' => 'Accountant', 'branch' => 'Erbil Head Office',
                'hired' => '2022-03-01', 'status' => 'active', 'structure' => 'Standard Staff (USD)', 'salary' => 1400,
                'shift' => 'MORNING', 'manager' => 3,
            ],
            13 => [
                'en' => 'Sara Al-Obaidi', 'ckb' => 'سارا ئەلعوبەیدی', 'ar' => 'سارة العبيدي',
                'gender' => 'female', 'email' => 'sara.obaidi@hrms.test', 'phone' => '+9647801000013',
                'dob' => '1995-04-05', 'marital' => 'single',
                'contact_name' => 'Ibrahim Al-Obaidi', 'contact_phone' => '7801000013', 'contact_relation' => 'parent',
                'department' => 'Finance & Accounting', 'position' => 'Accountant', 'branch' => 'Baghdad Branch',
                'hired' => '2023-01-09', 'status' => 'active', 'structure' => 'Standard Staff (USD)', 'salary' => 1350,
                'shift' => 'MORNING', 'manager' => 3,
            ],
            14 => [
                'en' => 'Rezan Jalal', 'ckb' => 'رێزان جەلال', 'ar' => 'ريزان جلال',
                'gender' => 'female', 'email' => 'rezan.jalal@hrms.test', 'phone' => '+9647701000014',
                'dob' => '1996-11-21', 'marital' => 'single',
                'contact_name' => 'Jalal Muhammed', 'contact_phone' => '7711000014', 'contact_relation' => 'parent',
                'department' => 'Finance & Accounting', 'position' => 'Accountant', 'branch' => 'Sulaymaniyah Branch',
                'hired' => '2023-05-15', 'status' => 'suspended', 'structure' => 'Standard Staff (USD)', 'salary' => 1300,
                'shift' => 'MORNING', 'manager' => 3,
            ],
            15 => [
                'en' => 'Dilan Hiwa', 'ckb' => 'دیلان هیوا', 'ar' => 'ديلان هيوا',
                'gender' => 'male', 'email' => 'dilan.hiwa@hrms.test', 'phone' => '+9647501000015',
                'dob' => '1993-07-30', 'marital' => 'single',
                'contact_name' => 'Hiwa Nabi', 'contact_phone' => '7701000015', 'contact_relation' => 'parent',
                'department' => 'Information Technology', 'position' => 'Software Engineer', 'branch' => 'Erbil Head Office',
                'hired' => '2022-09-05', 'status' => 'active', 'structure' => 'Standard Staff (USD)', 'salary' => 2000,
                'shift' => 'MORNING', 'manager' => 4,
            ],
            16 => [
                'en' => 'Rebin Qadir', 'ckb' => 'رێبین قادر', 'ar' => 'ريبين قادر',
                'gender' => 'male', 'email' => 'rebin.qadir@hrms.test', 'phone' => '+9647701000016',
                'dob' => '1994-02-11', 'marital' => 'married',
                'contact_name' => 'Shanaz Qadir', 'contact_phone' => '7711000016', 'contact_relation' => 'spouse',
                'department' => 'Information Technology', 'position' => 'Software Engineer', 'branch' => 'Sulaymaniyah Branch',
                'hired' => '2023-02-20', 'status' => 'active', 'structure' => 'Standard Staff (USD)', 'salary' => 1900,
                'shift' => 'MORNING', 'manager' => 4,
            ],
            17 => [
                'en' => 'Ali Al-Rubaie', 'ckb' => 'عەلی ئەلروبەعی', 'ar' => 'علي الربيعي',
                'gender' => 'male', 'email' => 'ali.rubaie@hrms.test', 'phone' => '+9647801000017',
                'dob' => '1989-09-16', 'marital' => 'married',
                'contact_name' => 'Suad Al-Rubaie', 'contact_phone' => '7801000017', 'contact_relation' => 'spouse',
                'department' => 'Information Technology', 'position' => 'Network Administrator', 'branch' => 'Baghdad Branch',
                'hired' => '2021-11-28', 'status' => 'active', 'structure' => 'Standard Staff (USD)', 'salary' => 1700,
                'shift' => 'MORNING', 'manager' => 4,
            ],
            18 => [
                'en' => 'Sipan Barzani', 'ckb' => 'سیپان بارزانی', 'ar' => 'سيبان البارزاني',
                'gender' => 'male', 'email' => 'sipan.barzani@hrms.test', 'phone' => '+9647501000018',
                'dob' => '1999-05-08', 'marital' => 'single',
                'contact_name' => 'Kamaran Barzani', 'contact_phone' => '7701000018', 'contact_relation' => 'sibling',
                'department' => 'Information Technology', 'position' => 'Software Engineer', 'branch' => 'Erbil Head Office',
                'hired' => '2026-08-16', 'status' => 'active', 'structure' => 'Standard Staff (USD)', 'salary' => 1600,
                'shift' => 'MORNING', 'manager' => 4,
            ],
            19 => [
                'en' => 'Yaser Mahmoud', 'ckb' => 'یاسر مەحمود', 'ar' => 'ياسر محمود',
                'gender' => 'male', 'email' => 'yaser.mahmoud@hrms.test', 'phone' => '+9647801000019',
                'dob' => '1984-03-02', 'marital' => 'married',
                'contact_name' => 'Amal Mahmoud', 'contact_phone' => '7801000019', 'contact_relation' => 'spouse',
                'department' => 'Operations', 'position' => 'Driver', 'branch' => 'Baghdad Branch',
                'hired' => '2021-06-06', 'status' => 'active', 'structure' => 'Field Staff (IQD)', 'salary' => 900000,
                'shift' => 'AFTERNOON', 'manager' => 5,
            ],
            20 => [
                'en' => 'Hassan Al-Basri', 'ckb' => 'حەسەن ئەلبەسری', 'ar' => 'حسن البصري',
                'gender' => 'male', 'email' => 'hassan.basri@hrms.test', 'phone' => '+9647711000020',
                'dob' => '1983-10-23', 'marital' => 'married',
                'contact_name' => 'Widad Al-Basri', 'contact_phone' => '7711000020', 'contact_relation' => 'spouse',
                'department' => 'Operations', 'position' => 'Driver', 'branch' => 'Sulaymaniyah Branch',
                'hired' => '2024-01-08', 'status' => 'active', 'structure' => 'Field Staff (IQD)', 'salary' => 850000,
                'shift' => 'AFTERNOON', 'manager' => 5, 'contract_expiry' => '2026-12-31',
            ],
            21 => [
                'en' => 'Ahmed Al-Jabouri', 'ckb' => 'ئەحمەد ئەلجەبووری', 'ar' => 'أحمد الجبوري',
                'gender' => 'male', 'email' => 'ahmed.jabouri@hrms.test', 'phone' => '+9647801000021',
                'dob' => '1990-12-12', 'marital' => 'married',
                'contact_name' => 'Israa Al-Jabouri', 'contact_phone' => '7801000021', 'contact_relation' => 'spouse',
                'department' => 'Sales & Marketing', 'position' => 'Sales Representative', 'branch' => 'Baghdad Branch',
                'hired' => '2022-11-14', 'status' => 'active', 'structure' => 'Standard Staff (USD)', 'salary' => 1250,
                'shift' => 'MORNING', 'manager' => 6,
            ],
            22 => [
                'en' => 'Fatima Al-Hashimi', 'ckb' => 'فاتیمە ئەلهاشیمی', 'ar' => 'فاطمة الهاشمي',
                'gender' => 'female', 'email' => 'fatima.hashimi@hrms.test', 'phone' => '+9647501000022',
                'dob' => '1997-01-19', 'marital' => 'single',
                'contact_name' => 'Hashim Abbas', 'contact_phone' => '7701000022', 'contact_relation' => 'parent',
                'department' => 'Sales & Marketing', 'position' => 'Sales Representative', 'branch' => 'Erbil Head Office',
                'hired' => '2023-09-18', 'status' => 'active', 'structure' => 'Standard Staff (USD)', 'salary' => 1200,
                'shift' => 'MORNING', 'manager' => 6,
            ],
            23 => [
                'en' => 'Havin Mustafa', 'ckb' => 'هاوین مستەفا', 'ar' => 'هافين مصطفى',
                'gender' => 'female', 'email' => 'havin.mustafa@hrms.test', 'phone' => '+9647501000023',
                'dob' => '1998-04-27', 'marital' => 'single',
                'contact_name' => 'Mustafa Amin', 'contact_phone' => '7701000023', 'contact_relation' => 'parent',
                'department' => 'Sales & Marketing', 'position' => 'Sales Representative', 'branch' => 'Duhok Branch',
                'hired' => '2024-03-04', 'status' => 'terminated', 'structure' => 'Standard Staff (USD)', 'salary' => 1150,
                'shift' => 'MORNING', 'manager' => 6,
            ],
            24 => [
                'en' => 'Omar Abdullah', 'ckb' => 'عومەر عەبدوڵا', 'ar' => 'عمر عبدالله',
                'gender' => 'male', 'email' => 'omar.abdullah@hrms.test', 'phone' => '+9647801000024',
                'dob' => '1987-08-08', 'marital' => 'married',
                'contact_name' => 'Hanan Abdullah', 'contact_phone' => '7801000024', 'contact_relation' => 'spouse',
                'department' => 'Logistics & Warehouse', 'position' => 'Warehouse Supervisor', 'branch' => 'Baghdad Branch',
                'hired' => '2022-05-23', 'status' => 'active', 'structure' => 'Field Staff (IQD)', 'salary' => 1200000,
                'shift' => 'AFTERNOON', 'manager' => 7,
            ],
            25 => [
                'en' => 'Chinar Sabir', 'ckb' => 'چنار سابیر', 'ar' => 'تشينار صابر',
                'gender' => 'female', 'email' => 'chinar.sabir@hrms.test', 'phone' => '+9647501000025',
                'dob' => '1996-02-29', 'marital' => 'single',
                'contact_name' => 'Sabir Hama', 'contact_phone' => '7701000025', 'contact_relation' => 'parent',
                'department' => 'Customer Support', 'position' => 'Customer Support Agent', 'branch' => 'Erbil Head Office',
                'hired' => '2023-10-02', 'status' => 'active', 'structure' => 'Standard Staff (USD)', 'salary' => 1000,
                'shift' => 'MORNING', 'manager' => 9,
            ],
            26 => [
                'en' => 'Noor Al-Saadi', 'ckb' => 'نوور ئەلسەعدی', 'ar' => 'نور السعدي',
                'gender' => 'female', 'email' => 'noor.saadi@hrms.test', 'phone' => '+9647801000026',
                'dob' => '1995-07-07', 'marital' => 'married',
                'contact_name' => 'Saad Kadhim', 'contact_phone' => '7801000026', 'contact_relation' => 'spouse',
                'department' => 'Customer Support', 'position' => 'Customer Support Agent', 'branch' => 'Baghdad Branch',
                'hired' => '2023-04-17', 'status' => 'active', 'structure' => 'Standard Staff (USD)', 'salary' => 1050,
                'shift' => 'NIGHT', 'manager' => 9,
            ],
            27 => [
                'en' => 'Zainab Al-Ani', 'ckb' => 'زەینەب ئەلعانی', 'ar' => 'زينب العاني',
                'gender' => 'female', 'email' => 'zainab.ani@hrms.test', 'phone' => '+9647711000027',
                'dob' => '2002-03-14', 'marital' => 'single',
                'contact_name' => 'Ani Faleh', 'contact_phone' => '7711000027', 'contact_relation' => 'parent',
                'department' => 'Customer Support', 'position' => 'Customer Support Agent', 'branch' => 'Sulaymaniyah Branch',
                'hired' => '2026-07-01', 'status' => 'active', 'structure' => 'Standard Staff (USD)', 'salary' => 600,
                'shift' => 'NIGHT', 'manager' => 9,
            ],
            28 => [
                'en' => 'Mariam Abdulrahman', 'ckb' => 'مریەم عەبدولرەحمان', 'ar' => 'مريم عبدالرحمن',
                'gender' => 'female', 'email' => 'mariam.abdulrahman@hrms.test', 'phone' => '+9647801000028',
                'dob' => '1991-11-11', 'marital' => 'married',
                'contact_name' => 'Abdulrahman Nouri', 'contact_phone' => '7801000028', 'contact_relation' => 'parent',
                'department' => 'Legal Affairs', 'position' => 'Legal Advisor', 'branch' => 'Baghdad Branch',
                'hired' => '2024-06-10', 'status' => 'active', 'structure' => 'Standard Staff (USD)', 'salary' => 1800,
                'shift' => 'MORNING', 'manager' => 8, 'contract_expiry' => '2027-06-09',
            ],
            29 => [
                'en' => 'Kareem Al-Zaidi', 'ckb' => 'کەریم ئەلزەیدی', 'ar' => 'كريم الزيدي',
                'gender' => 'male', 'email' => 'kareem.zaidi@hrms.test', 'phone' => '+9647501000029',
                'dob' => '1985-06-21', 'marital' => 'married',
                'contact_name' => 'Ruqaya Al-Zaidi', 'contact_phone' => '7701000029', 'contact_relation' => 'spouse',
                'department' => 'Logistics & Warehouse', 'position' => 'Driver', 'branch' => 'Duhok Branch',
                'hired' => '2023-08-07', 'status' => 'resigned', 'structure' => 'Field Staff (IQD)', 'salary' => 800000,
                'shift' => 'AFTERNOON', 'manager' => 7,
            ],
            30 => [
                'en' => 'Tariq Al-Douri', 'ckb' => 'تاریق ئەلدووری', 'ar' => 'طارق الدوري',
                'gender' => 'male', 'email' => 'tariq.douri@hrms.test', 'phone' => '+9647801000030',
                'dob' => '1992-09-09', 'marital' => 'single',
                'contact_name' => 'Douri Salman', 'contact_phone' => '7801000030', 'contact_relation' => 'parent',
                'department' => 'Sales & Marketing', 'position' => 'Marketing Specialist', 'branch' => 'Baghdad Branch',
                'hired' => '2024-10-20', 'status' => 'active', 'structure' => 'Standard Staff (USD)', 'salary' => 1400,
                'shift' => 'MORNING', 'manager' => 6,
            ],
            // Signed, but does not start until next month.
            31 => [
                'en' => 'Alan Sarkawt', 'ckb' => 'ئالان سەرکەوت', 'ar' => 'آلان سركوت',
                'gender' => 'male', 'email' => 'alan.sarkawt@hrms.test', 'phone' => '+9647501000031',
                'dob' => '2000-02-17', 'marital' => 'single',
                'contact_name' => 'Sarkawt Aziz', 'contact_phone' => '7701000031', 'contact_relation' => 'parent',
                'department' => 'Information Technology', 'position' => 'Software Engineer', 'branch' => 'Erbil Head Office',
                'hired' => '2026-11-01', 'status' => 'future_hired', 'structure' => 'Standard Staff (USD)', 'salary' => 1750,
                'shift' => 'MORNING', 'manager' => 4,
            ],
        ];
    }
}
