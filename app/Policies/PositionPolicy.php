<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Position;
use Illuminate\Auth\Access\HandlesAuthorization;

class PositionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Position');
    }

    public function view(AuthUser $authUser, Position $position): bool
    {
        return $authUser->can('View:Position');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Position');
    }

    public function update(AuthUser $authUser, Position $position): bool
    {
        return $authUser->can('Update:Position');
    }

    public function delete(AuthUser $authUser, Position $position): bool
    {
        return $authUser->can('Delete:Position');
    }

}