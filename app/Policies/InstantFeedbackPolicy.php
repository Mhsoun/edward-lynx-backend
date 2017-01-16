<?php

namespace App\Policies;

use App\Models\User;
use App\Models\InstantFeedback;
use Illuminate\Auth\Access\HandlesAuthorization;

class InstantFeedbackPolicy
{
    use HandlesAuthorization;
    
    /**
     * Before hook. Superadmins can do everything.
     * 
     * @param  User $user
     * @return boolean
     */
    public function before(User $user)
    {
        if ($user->isA('superadmin')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the instantFeedback.
     *
     * @param  \App\User  $user
     * @param  \App\InstantFeedback  $instantFeedback
     * @return mixed
     */
    public function view(User $user, InstantFeedback $instantFeedback)
    {
        return $instantFeedback->user_id == $user->id;
    }

    /**
     * Determine whether the user can create instantFeedbacks.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the instantFeedback.
     *
     * @param  \App\User  $user
     * @param  \App\InstantFeedback  $instantFeedback
     * @return mixed
     */
    public function update(User $user, InstantFeedback $instantFeedback)
    {
        return $instantFeedback->user_id == $user->id;
    }

    /**
     * Determine whether the user can delete the instantFeedback.
     *
     * @param  \App\User  $user
     * @param  \App\InstantFeedback  $instantFeedback
     * @return mixed
     */
    public function delete(User $user, InstantFeedback $instantFeedback)
    {
        return $instantFeedback->user_id == $user->id;
    }
}
