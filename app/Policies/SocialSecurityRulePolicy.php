<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SocialSecurityRule;
use Illuminate\Auth\Access\HandlesAuthorization;

class SocialSecurityRulePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SocialSecurityRule');
    }

    public function view(AuthUser $authUser, SocialSecurityRule $socialSecurityRule): bool
    {
        return $authUser->can('View:SocialSecurityRule');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SocialSecurityRule');
    }

    public function update(AuthUser $authUser, SocialSecurityRule $socialSecurityRule): bool
    {
        return $authUser->can('Update:SocialSecurityRule');
    }

    public function delete(AuthUser $authUser, SocialSecurityRule $socialSecurityRule): bool
    {
        return $authUser->can('Delete:SocialSecurityRule');
    }
    
}