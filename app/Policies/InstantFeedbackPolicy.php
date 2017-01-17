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
     * Determine whether the user can view the instant feedback.
     *
     * @param   \App\User  $user
     * @param   \App\InstantFeedback  $instantFeedback
     * @return  mixed
     */
    public function view(User $user, InstantFeedback $instantFeedback)
    {
        if ($instantFeedback->user_id == $user->id) {
            return true;
        } elseif ($instantFeedback->recipients()->where('user_id', $user->id)->count() > 0) {
            return true;
        } elseif ($instantFeedback->isSharedTo($user)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determine whether the user can create instant feedbacks.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }
    
    /**
     * Determine whether the user can submit answers to this instant feedback.
     *
     * @param   App\Models\User             $user
     * @param   App\Models\InstantFeedback  $instantFeedback
     * @return  boolean
     */
    public function answer(User $user, InstantFeedback $instantFeedback)
    {
        return $this->view($user, $instantFeedback);
    }
    
    /**
     * Determine whether the user can view instant feedback answers.
     *
     * @param   App\Models\User             $user
     * @param   App\Models\InstantFeedback  $instantFeedback
     * @return  boolean    
     */
    public function viewAnswers(User $user, InstantFeedback $instantFeedback)
    {
        if ($instantFeedback->user_id == $user->id) {
            return true;
        } elseif ($instantFeedback->isSharedTo($user)) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Determine whether the user can share instant feedback answers.
     *
     * @param   App\Models\User             $user
     * @param   App\Models\InstantFeedback  $instantFeedback
     * @return  boolean   
     */
    public function share(User $user, InstantFeedback $instantFeedback)
    {
        return $instantFeedback->user_id == $user->id;
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
