<?php

namespace App\Policies;

use App\Models\OrganizationInvitation;
use App\Models\User;

class OrganizationInvitationPolicy
{
    public function accept(User $user, OrganizationInvitation $invitation): bool
    {
        return strcasecmp($user->email, $invitation->email) === 0;
    }
}
