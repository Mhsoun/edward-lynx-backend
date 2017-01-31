<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Before hook. Superadmins can do everything.
     * 
     * @param   App\Models\User     $user
     * @return  boolean
     */
    public function before(User $user)
    {
        if ($user->isA('superadmin')) {
            return true;
        }
    }

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
