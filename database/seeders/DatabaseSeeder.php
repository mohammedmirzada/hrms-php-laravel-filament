<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Support\ActivityLogStatus;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Documents are written to the public disk, so the symlink has to exist.
        if (! file_exists(public_path('storage'))) {
            Artisan::call('storage:link');
        }

        $this->call(UserSeeder::class);

        // Acting as the admin fills in created_by / updated_by across the demo data.
        Auth::login(User::where('email', 'admin@hrms.test')->firstOrFail());

        // Thousands of demo rows would otherwise bury the activity log.
        $logStatus = app(ActivityLogStatus::class);
        $logStatus->disable();

        $this->call([
            OrganizationSeeder::class,
            SalaryStructureSeeder::class,
            ShiftSeeder::class,
            LeaveSetupSeeder::class,
            EmployerSeeder::class,
            HolidaySeeder::class,
            PayrollSeeder::class,
            AttendanceSeeder::class,
            LeaveDataSeeder::class,
        ]);

        $logStatus->enable();
        $this->seedActivityTrail();

        Auth::logout();

        $this->command->newLine();
        $this->command->info('Demo data ready.');
        $this->command->table(
            ['Login', 'Email', 'Password'],
            [
                ['Super Admin', 'admin@hrms.test', 'password'],
                ['HR Manager', 'hr@hrms.test', 'password'],
                ['Viewer', 'viewer@hrms.test', 'password'],
            ]
        );
    }

    /** A short, readable trail so the Activity Log page is not empty. */
    private function seedActivityTrail(): void
    {
        $employer = Employer::orderBy('id')->skip(9)->first();

        if ($employer) {
            $employer->update(['phone_number_2' => '+9647509998888']);
            $employer->update(['marital_status' => 'married']);
        }

        $branch = Branch::orderBy('id')->first();

        if ($branch) {
            $branch->update([
                'address' => $branch->getTranslations('address') + ['en' => '60m Street, Building 12, Erbil, Kurdistan Region'],
            ]);
        }

        $department = Department::orderBy('id')->first();

        if ($department) {
            $department->update([
                'name' => $department->getTranslations('name') + ['en' => 'Human Resources & Administration'],
            ]);
        }
    }
}
