<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AttendanceBranchSetting;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttendanceBranchSettingPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AttendanceBranchSetting');
    }

    public function view(AuthUser $authUser, AttendanceBranchSetting $attendanceBranchSetting): bool
    {
        return $authUser->can('View:AttendanceBranchSetting');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AttendanceBranchSetting');
    }

    public function update(AuthUser $authUser, AttendanceBranchSetting $attendanceBranchSetting): bool
    {
        return $authUser->can('Update:AttendanceBranchSetting');
    }

    public function delete(AuthUser $authUser, AttendanceBranchSetting $attendanceBranchSetting): bool
    {
        return $authUser->can('Delete:AttendanceBranchSetting');
    }

}