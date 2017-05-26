<?php namespace App\Policies;

use App\SurveyTypes;
use App\Models\User;
use App\Models\Survey;
use App\Models\Recipient;
use App\Models\SurveyRecipient;

class SurveyPolicy extends Policy
{

    /**
     * Determine whether the user can view the survey.
     *
     * @param   App\User    $user
     * @param   App\Survey  $survey
     * @return  boolean
     */
    public function view(User $user, Survey $survey)
    {
        if ($this->administer($user, $instantFeedback)) {
            return true;
        } elseif ($survey->ownerId == $user->id) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determine whether the user can view all surveys.
     *
     * @param  \App\User  $user
     * @return boolean
     */
    public function viewAll(User $user)
    {
        return $user->isA(User::SUPERADMIN);
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
        if ($this->administer($user, $instantFeedback)) {
            return true;
        } elseif ($survey->ownerId == $user->id) {
            return true;
        } else {
            return false;
        }
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
        if ($this->administer($user, $instantFeedback)) {
            return true;
        } elseif ($survey->ownerId == $user->id) {
            return true;
        } else {
            return false;
        }
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
        $recipient = Recipient::findForOwner($survey->ownerId, $user->email);
        $surveyRecipient = SurveyRecipient::where('recipientId', $recipient)
                                ->first();
        return $recipient->count() > 0;
    }
    
    /**
     * Determine whether the user can view survey results.
     * 
     * @param   App\Models\User     $user
     * @param   App\Models\Survey   $survey
     * @return  boolean
     */
    public function viewAnswers(User $user, Survey $survey)
    {
        if ($this->administer($user, $survey)) {
            return true;
        } elseif ($this->supervise($user, $survey)) {
            return true;
        } elseif ($survey->ownerId == $user->id) {
            return true;
        } else {
            return false;
        }
    }
}
