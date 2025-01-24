<?php

namespace App\Policies;

use App\Models\App;
use App\Models\User;

class AppPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, App $app): bool
    {
        return $app->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, App $app): bool
    {
        return $app->user_id === $user->id;
    }
}
