<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Survey;
use Illuminate\Auth\Access\HandlesAuthorization;

class SurveyPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the survey.
     *
     * @param  \App\User  $user
     * @param  \App\Survey  $survey
     * @return boolean
     */
    public function view(User $user, Survey $survey)
    {
        return $user->id == 1;
    }

    /**
     * Determine whether the user can create surveys.
     *
     * @param  \App\User  $user
     * @return boolean
     */
    public function create(User $user)
    {
        return $user->id == 1;
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
        return $user->id == 1;
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
        return $user->id == 1;
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
        return $user->id == 1;
    }
}
