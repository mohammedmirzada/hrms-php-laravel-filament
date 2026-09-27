<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Shield needs its permissions in place before roles can be built.
        // Permissions only: --all would regenerate app/Policies and overwrite the
        // hand-edited policies that are committed to the repo.
        Artisan::call('shield:generate', [
            '--all' => true,
            '--option' => 'permissions',
            '--panel' => 'admin',
            '--no-interaction' => true,
        ]);

        $admin = User::create([
            'name' => 'Aram Kamal',
            'email' => 'admin@hrms.test',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);

        $hr = User::create([
            'name' => 'Shirin Osman',
            'email' => 'hr@hrms.test',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);

        $viewer = User::create([
            'name' => 'Layla Hussein',
            'email' => 'viewer@hrms.test',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);

        Artisan::call('shield:super-admin', ['--user' => $admin->id, '--panel' => 'admin']);

        $all = Permission::pluck('name');

        // Shield names its permissions "Verb:Entity", e.g. "ViewAny:Employer".
        $pages = [
            'View:Dashboard', 'View:AttendanceReport', 'View:AttendanceCalendar',
            'View:DocumentExpiryReport', 'View:EmployeeReport', 'View:LeaveBalanceReport',
            'View:LeaveUsageReport', 'View:ProbationContractReport',
            'View:ExpiredDocumentsWidget', 'View:HolidaysWidget',
        ];

        // HR runs people, leave and attendance, but not payroll or access control.
        $hrFull = [
            'Employer', 'Document', 'LeaveRequest', 'LeaveType', 'LeavePolicy',
            'LeaveBalances', 'LeaveLedgerEntry', 'Holiday', 'AttendanceEvent',
            'AttendanceDevice', 'EmployerShift', 'Shift',
        ];

        $hrReadOnly = ['Department', 'Position', 'Branch', 'EmploymentStatus', 'Activity'];

        $hrPermissions = [];

        foreach ($hrFull as $entity) {
            foreach (['ViewAny', 'View', 'Create', 'Update', 'Delete'] as $verb) {
                $hrPermissions[] = "{$verb}:{$entity}";
            }
        }

        foreach ($hrReadOnly as $entity) {
            $hrPermissions[] = "ViewAny:{$entity}";
            $hrPermissions[] = "View:{$entity}";
        }

        $hrRole = Role::firstOrCreate(['name' => 'HR Manager', 'guard_name' => 'web']);
        $hrRole->syncPermissions(
            $all->intersect(array_merge($hrPermissions, $pages))->values()
        );

        // A read-only role for everything except users and roles.
        $viewerRole = Role::firstOrCreate(['name' => 'Viewer', 'guard_name' => 'web']);
        $viewerRole->syncPermissions(
            $all->filter(fn (string $p) => (str_starts_with($p, 'View:') || str_starts_with($p, 'ViewAny:'))
                && ! str_ends_with($p, ':Role')
                && ! str_ends_with($p, ':User'))
                ->values()
        );

        $hr->assignRole($hrRole);
        $viewer->assignRole($viewerRole);

        Artisan::call('permission:cache-reset');
    }
}
