<?php

namespace App\Policies;

use App\Models\App;
use App\Models\Organization;
use App\Models\User;

class AppPolicy
{
    /**
     * Determine whether the user can create a new app in the organization.
     */
    public function create(User $user, Organization $organization): bool
    {
        return $user->isOrganizationAdmin($organization);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, App $app): bool
    {
        if (! $app->organization) {
            return false;
        }

        return $user->isOrganizationMember($app->organization);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, App $app): bool
    {
        if (! $app->organization) {
            return false;
        }

        return $user->isOrganizationAdmin($app->organization);
    }
}
