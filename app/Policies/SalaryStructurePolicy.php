<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SalaryStructure;
use Illuminate\Auth\Access\HandlesAuthorization;

class SalaryStructurePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SalaryStructure');
    }

    public function view(AuthUser $authUser, SalaryStructure $salaryStructure): bool
    {
        return $authUser->can('View:SalaryStructure');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SalaryStructure');
    }

    public function update(AuthUser $authUser, SalaryStructure $salaryStructure): bool
    {
        return $authUser->can('Update:SalaryStructure');
    }

    public function delete(AuthUser $authUser, SalaryStructure $salaryStructure): bool
    {
        return $authUser->can('Delete:SalaryStructure');
    }

}