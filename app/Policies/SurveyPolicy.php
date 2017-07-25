<?php namespace App\Policies;

use App\SurveyTypes;
use App\Models\User;
use App\Models\Survey;
use App\Models\Recipient;
use App\Models\SurveyCandidate;
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
        if ($this->administer($user, $survey)) {
            return true;
        } elseif ($survey->ownerId == $user->id) {
            return true;
        } elseif ($this->invited($user, $survey)) {
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
        if ($this->administer($user, $survey)) {
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
        if ($this->administer($user, $survey)) {
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
        return $this->invited($user, $survey);
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
        } elseif (SurveyCandidate::isCandidateOf($survey, $user->email)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns TRUE if the provided user has been invited to the survey.
     * 
     * @param  App\Models\User      $user
     * @param  App\Models\Survey    $survey
     * @return boolean
     */
    public function invited(User $user, Survey $survey)
    {
        $recipients = Recipient::where('mail', $user->email)
                        ->get()
                        ->map(function($item) {
                            return $item->id;
                        })
                        ->toArray();
        if ($survey->candidates()->whereIn('recipientId', $recipients)->count() > 0) {
            return true;
        } elseif ($survey->recipients()->whereIn('recipientId', $recipients)->count() > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns TRUE if the provided user can invite others in a survey.
     * 
     * @param  App\Models\User  $user
     * @param  App\ModelsSurvey $survey
     * @return boolean
     */
    public function invite(User $user, Survey $survey)
    {
        if ($recipient = Recipient::findForOwner($survey->ownerId, $user->email)) {
            $surveyRecipient = SurveyRecipient::where([
                'surveyId'      => $survey->id,
                'recipientId'   => $recipient->id,
                'roleId'        => 1
            ])->first();
            if ($surveyRecipient) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
