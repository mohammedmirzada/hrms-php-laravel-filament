<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Shift;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShiftPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Shift');
    }

    public function view(AuthUser $authUser, Shift $shift): bool
    {
        return $authUser->can('View:Shift');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Shift');
    }

    public function update(AuthUser $authUser, Shift $shift): bool
    {
        return $authUser->can('Update:Shift');
    }

    public function delete(AuthUser $authUser, Shift $shift): bool
    {
        return $authUser->can('Delete:Shift');
    }

}