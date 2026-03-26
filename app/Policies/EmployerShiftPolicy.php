<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\EmployerShift;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployerShiftPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EmployerShift');
    }

    public function view(AuthUser $authUser, EmployerShift $employerShift): bool
    {
        return $authUser->can('View:EmployerShift');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EmployerShift');
    }

    public function update(AuthUser $authUser, EmployerShift $employerShift): bool
    {
        return $authUser->can('Update:EmployerShift');
    }

    public function delete(AuthUser $authUser, EmployerShift $employerShift): bool
    {
        return $authUser->can('Delete:EmployerShift');
    }

}