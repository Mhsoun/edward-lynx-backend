<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy extends Policy
{

    /**
     * Determine whether the user can view another user's details.
     *
     * @param   App\User  $user
     * @return  boolean
     */
    public function view(User $currentUser, User $user)
    {
        return $currentUser->colleagueOf($user);
    }
}
