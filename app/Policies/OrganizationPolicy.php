<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function view(User $user, Organization $organization): bool
    {
        return $user->isOrganizationMember($organization);
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->isOrganizationAdmin($organization);
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $user->ownsOrganization($organization);
    }

    public function invite(User $user, Organization $organization): bool
    {
        return $user->isOrganizationAdmin($organization);
    }
}
