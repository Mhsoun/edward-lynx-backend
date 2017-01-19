<?php namespace App\Policies;

use App\SurveyTypes;
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
        if ($user->isA('superadmin')) {
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
     * Determine whether the user can view all surveys.
     *
     * @param  \App\User  $user
     * @return boolean
     */
    public function viewAll(User $user)
    {
        return $user->isA('superadmin');
    }

    /**
     * Determine whether the user can create a survey
     * with the given type.
     *
     * @param   \App\User   $user
     * @param   string      $type
     * @return  boolean
     */
    public function create(User $user, $type)
    {
        if ($type === SurveyTypes::Instant) {
            return true;
        }
        
        return SurveyTypes::canCreate($user->allowedSurveyTypes, $type);
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
     * @param   App\Models\User     $user
     * @param   App\Models\Survey   $survey
     * @return  boolean
     */
    public function answer(User $user, Survey $survey)
    {
        return true; // Validate key in the controller.
    }
}
