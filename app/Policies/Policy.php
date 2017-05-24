<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

abstract class Policy
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
        if ($user->isA(User::SUPERADMIN)) {
            return true;
        }
    }
    
    /**
     * Determine whether the user can administer the Development Plan.
     *
     * @param   App\Models\User $user
     * @param   mixed           $object
     * @return  bool
     */
    public function administer(User $user, $object)
    {
        $owner = $object->owner;
        if (!$owner) {
            $owner = $object->user;
        }

        return $user->isA(User::ADMIN) && $owner->colleagueOf($user);
    }
    
    /**
     * Determine whether the user can supervise the Development Plan.
     *
     * @param   App\Models\User $user
     * @param   mixed           $devPlan
     * @return bool
     */
    public function supervise(User $user, $object)
    {
        return $user->isA(User::SUPERVISOR) && $object->owner->colleagueOf($user);
    }
    
}