<?php namespace App\Policies;

use App\Models\User;
use App\Models\Survey;
use Illuminate\Auth\Access\HandlesAuthorization;

class SurveyPolicy
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
        if ($user->is('superadmin')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the survey.
     *
     * @param  \App\User  $user
     * @param  \App\Survey  $survey
     * @return boolean
     */
    public function view(User $user, Survey $survey)
    {
        if ($survey->ownerId == $user->id) {
            return true;
        }
        
        return false;
    }

    /**
     * Determine whether the user can create surveys.
     *
     * @param  \App\User  $user
     * @return boolean
     */
    public function create(User $user)
    {
        if ($user->is('admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the survey.
     *
     * @param  \App\User  $user
     * @param  \App\Survey  $survey
     * @return boolean
     */
    public function update(User $user, Survey $survey)
    {
        if ($survey->ownerId == $user->id) {
            return true;
        }
        
        return false;
    }

    /**
     * Determine whether the user can delete the survey.
     *
     * @param  \App\User  $user
     * @param  \App\Survey  $survey
     * @return boolean
     */
    public function delete(User $user, Survey $survey)
    {
        if ($survey->ownerId == $user->id) {
            return true;
        }
        
        return false;
    }

    /**
     * Determine whether the user can answer the survey.
     * 
     * @param  User   $user
     * @param  Survey $survey
     * @return boolean
     */
    public function answer(User $user, Survey $survey)
    {
        if ($user->is('admin')) {
            return true;
        }

        return false;
    }
}
