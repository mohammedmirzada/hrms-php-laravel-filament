<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Holiday;
use Illuminate\Auth\Access\HandlesAuthorization;

class HolidayPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Holiday');
    }

    public function view(AuthUser $authUser, Holiday $holiday): bool
    {
        return $authUser->can('View:Holiday');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Holiday');
    }

    public function update(AuthUser $authUser, Holiday $holiday): bool
    {
        return $authUser->can('Update:Holiday');
    }

    public function delete(AuthUser $authUser, Holiday $holiday): bool
    {
        return $authUser->can('Delete:Holiday');
    }

}