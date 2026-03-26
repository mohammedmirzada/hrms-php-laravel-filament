<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\LeavePolicy;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeavePolicyPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LeavePolicy');
    }

    public function view(AuthUser $authUser, LeavePolicy $leavePolicy): bool
    {
        return $authUser->can('View:LeavePolicy');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LeavePolicy');
    }

    public function update(AuthUser $authUser, LeavePolicy $leavePolicy): bool
    {
        return $authUser->can('Update:LeavePolicy');
    }

    public function delete(AuthUser $authUser, LeavePolicy $leavePolicy): bool
    {
        return $authUser->can('Delete:LeavePolicy');
    }

}