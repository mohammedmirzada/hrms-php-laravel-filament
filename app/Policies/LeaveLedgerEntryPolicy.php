<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\LeaveLedgerEntry;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeaveLedgerEntryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LeaveLedgerEntry');
    }

    public function view(AuthUser $authUser, LeaveLedgerEntry $leaveLedgerEntry): bool
    {
        return $authUser->can('View:LeaveLedgerEntry');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LeaveLedgerEntry');
    }

    public function update(AuthUser $authUser, LeaveLedgerEntry $leaveLedgerEntry): bool
    {
        return $authUser->can('Update:LeaveLedgerEntry');
    }

    public function delete(AuthUser $authUser, LeaveLedgerEntry $leaveLedgerEntry): bool
    {
        return $authUser->can('Delete:LeaveLedgerEntry');
    }

}