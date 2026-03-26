<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\EmploymentStatus;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmploymentStatusPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EmploymentStatus');
    }

    public function view(AuthUser $authUser, EmploymentStatus $employmentStatus): bool
    {
        return $authUser->can('View:EmploymentStatus');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EmploymentStatus');
    }

    public function update(AuthUser $authUser, EmploymentStatus $employmentStatus): bool
    {
        return $authUser->can('Update:EmploymentStatus');
    }

    public function delete(AuthUser $authUser, EmploymentStatus $employmentStatus): bool
    {
        return $authUser->can('Delete:EmploymentStatus');
    }

}