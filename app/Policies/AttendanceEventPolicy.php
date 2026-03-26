<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AttendanceEvent;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttendanceEventPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AttendanceEvent');
    }

    public function view(AuthUser $authUser, AttendanceEvent $attendanceEvent): bool
    {
        return $authUser->can('View:AttendanceEvent');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AttendanceEvent');
    }

    public function update(AuthUser $authUser, AttendanceEvent $attendanceEvent): bool
    {
        return $authUser->can('Update:AttendanceEvent');
    }

    public function delete(AuthUser $authUser, AttendanceEvent $attendanceEvent): bool
    {
        return $authUser->can('Delete:AttendanceEvent');
    }

}