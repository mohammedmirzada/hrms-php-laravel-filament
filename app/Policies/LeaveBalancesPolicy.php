<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\LeaveBalances;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeaveBalancesPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LeaveBalances');
    }

    public function view(AuthUser $authUser, LeaveBalances $leaveBalances): bool
    {
        return $authUser->can('View:LeaveBalances');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LeaveBalances');
    }

    public function update(AuthUser $authUser, LeaveBalances $leaveBalances): bool
    {
        return $authUser->can('Update:LeaveBalances');
    }

    public function delete(AuthUser $authUser, LeaveBalances $leaveBalances): bool
    {
        return $authUser->can('Delete:LeaveBalances');
    }

}