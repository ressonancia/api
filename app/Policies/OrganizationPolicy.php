<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function view(User $user, Organization $organization): bool
    {
        return $user->organizations()
            ->where('organizations.id', $organization->id)
            ->exists();
    }

    public function edit(User $user, Organization $organization): bool
    {
        return $user->organizations()
            ->where('organizations.id', $organization->id)
            ->wherePivotIn('role', [
                Organization::ROLE_OWNER,
                Organization::ROLE_ADMIN,
            ])
            ->exists();
    }
}
