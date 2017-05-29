<?php namespace App;

use Auth;
use App\Models\User;
use App\Models\Survey;
use App\Models\EmailText;
use App\Models\Recipient;
use App\Events\SurveyCreated;
use App\Notifications\InviteOthersToEvaluate;

/**
* Contains functions for surveys
*/
abstract class Surveys
{
    /*
    * Parses the given date as a start date
    */
    public static function parseStartDate($date)
    {
        $startDate = \Carbon\Carbon::parse($date);
        $startDate->hour = 0;
        $startDate->minute = 0;
        $startDate->second = 0;
        return $startDate;
    }

    /**
    * Parses the given date as an end date
    */
    public static function parseEndDate($date)
    {
        $endDate = \Carbon\Carbon::parse($date);
        $endDate->hour = 23;
        $endDate->minute = 59;
        $endDate->second = 59;
        return $endDate;
    }

    /**
     * Indicates if the authenticated user can edit the given survey
     */
    public static function canEditSurvey($survey)
    {
        if (Auth::user()->isAdmin) {
            return true;
        } else {
            return $survey->ownerId == Auth::user()->id;
        }
    }

    /**
    * Creates the email text
    */
    private static function createEmailText($ownerId, $lang, $email)
    {
		return EmailText::make(User::find($ownerId), $email->subject, $email->text, $lang);
    }

    /**
    * Saves the email texts for the given survey
    */
    private static function saveEmailTexts($survey, $surveyData)
    {
        $emails = [
            'invitation' => 'invitationTextId',
            'reminder' => 'manualRemindingTextId',
            'toEvaluate' => 'toEvaluateInvitationTextId',
            'inviteOthersReminder' => 'inviteOthersReminderTextId',
            'candidateInvite' => 'candidateInvitationTextId',
            'userReport' => 'userReportTextId',
            'toEvaluateRole' => 'evaluatedTeamInvitationTextId',
        ];
		
		$owner = User::find($surveyData->ownerId);

        foreach ($emails as $email => $columnName) {
            if (property_exists($surveyData->emails, $email)) {
				$email = $surveyData->emails->{$email};
				$emailText = EmailText::make($owner, $email->subject, $email->text, $surveyData->lang);
                $survey[$columnName] = $emailText->id;
            }
        }
    }

    /**
    * Saves the categories and questions for the given survey
    */
    private static function saveCategoriesAndQuestions($survey, $categories, $questions)
    {
        $categoryMapping = [];

        foreach ($categories as $category) {
            $surveyCategory = new \App\Models\SurveyQuestionCategory;
            $surveyCategory->categoryId = \App\Models\QuestionCategory::find($category->id)->copy()->id;
            $categoryMapping[$category->id] = $surveyCategory->categoryId;
            $surveyCategory->order = $category->order;
            $survey->categories()->save($surveyCategory);
        }

        foreach ($questions as $question) {
            $actualQuestion = \App\Models\Question::find($question->id);
            $questionId = $actualQuestion->copy(true, $categoryMapping[$actualQuestion->categoryId])->id;

            $surveyQuestion = new \App\Models\SurveyQuestion;
            $surveyQuestion->questionId = $questionId;
            $surveyQuestion->order = $question->order;
            $survey->questions()->save($surveyQuestion);

            if (SurveyTypes::isGroupLike($survey->type)) {
                $surveyQuestion->addTargetRoles($question->targetRoles);
            }
        }
    }

    /**
    * Creates a survey from the given data
    */
    public static function create($app, $surveyData)
    {
        $type = $surveyData->type;

        //Create base survey
        $survey = new Survey;
        $survey->name = $surveyData->name;
        $survey->type = $surveyData->type;
        $survey->lang = $surveyData->lang;
        $survey->ownerId = $surveyData->ownerId;

        $survey->startDate = $surveyData->startDate;
        $survey->endDate = $surveyData->endDate;

        $survey->description = $surveyData->description;
        $survey->thankYouText = $surveyData->thankYou;
        $survey->questionInfoText = $surveyData->questionInfo;

        if ($type == SurveyTypes::Progress) {
            $survey->createUserReports = true;
        }

        if (SurveyTypes::isIndividualLike($type)) {
            $survey->inviteText = $surveyData->individual->inviteText;
        }

        //Save the email texts
        Surveys::saveEmailTexts($survey, $surveyData);
        $survey->save();

        //Save the categories & questions
        Surveys::saveCategoriesAndQuestions($survey, $surveyData->categories, $surveyData->questions);

        //Create type specific objects
        if ($type == SurveyTypes::Individual || $type == SurveyTypes::Progress) {
            Surveys::createIndividual($app, $survey, $surveyData);
        } else if (SurveyTypes::isGroupLike($type)) {
            Surveys::createGroup($app, $survey, $surveyData);
        } else if ($type == SurveyTypes::Normal) {
            Surveys::createNormal($app, $survey, $surveyData);
        } else if ($type == SurveyTypes::Instant) {
            Surveys::createInstant($survey, $surveyData->recipients);
        }
        
        event(new SurveyCreated($survey));

        return $survey;
    }

    /**
    * Creates a copy of the given survey
    */
    public static function copy($app, $survey, $data)
    {
        $newSurvey = $survey->replicate();
        $newSurvey->name = $data->name;
        $newSurvey->startDate = Surveys::parseStartDate($data->startDate);
        $newSurvey->endDate = Surveys::parseEndDate($data->endDate);
        $newSurvey->compareAgainstSurveyId = $survey->id;

        //Other texts
        $newSurvey->description = $data->description;
        $newSurvey->inviteText = $data->inviteText;
        $newSurvey->questionInfoText = $data->questionInfoText;
        $newSurvey->thankYouText = $data->thankYouText;

        //Email texts
        $updateEmail = function ($emailText, $name) use (&$data, &$newSurvey) {
            if (isset($data->{$name})) {
                $emailText->subject = $data->{$name}->subject;
                $emailText->text = $data->{$name}->text;
            }
        };

        if ($newSurvey->invitationTextId != null) {
            $invitationText = $survey->invitationText->replicate();
            $updateEmail($invitationText, 'invitationText');
            $invitationText->save();
            $newSurvey->invitationTextId = $invitationText->id;
        }

        if ($newSurvey->manualRemindingTextId != null) {
            $manualRemindingText = $survey->manualRemindingText->replicate();
            $updateEmail($manualRemindingText, 'manualRemindingText');
            $manualRemindingText->save();
            $newSurvey->manualRemindingTextId = $manualRemindingText->id;
        }

        if ($newSurvey->toEvaluateInvitationTextId != null) {
            $toEvaluateText = $survey->toEvaluateText->replicate();
            $updateEmail($toEvaluateText, 'toEvaluateText');
            $toEvaluateText->save();
            $newSurvey->toEvaluateInvitationTextId = $toEvaluateText->id;
        }

        if ($newSurvey->evaluatedTeamInvitationTextId != null) {
            $evaluatedTeamInvitationText = $survey->evaluatedTeamInvitationText->replicate();
            $updateEmail($evaluatedTeamInvitationText, 'evaluatedTeamInvitationText');
            $evaluatedTeamInvitationText->save();
            $newSurvey->evaluatedTeamInvitationTextId = $evaluatedTeamInvitationText->id;
        }

        if ($newSurvey->candidateInvitationTextId != null) {
            $candidateInvitationText = $survey->candidateInvitationText->replicate();
            $updateEmail($candidateInvitationText, 'candidateInvitationText');
            $candidateInvitationText->save();
            $newSurvey->candidateInvitationTextId = $candidateInvitationText->id;
        }

        if ($newSurvey->userReportTextId != null) {
            $userReportText = $survey->userReportText->replicate();
            $updateEmail($userReportText, 'userReportText');
            $userReportText->save();
            $newSurvey->userReportTextId = $userReportText->id;
        }

        if ($newSurvey->inviteOthersReminderTextId != null) {
            $inviteOthersRemindingText = $survey->inviteOthersRemindingText->replicate();
            $updateEmail($inviteOthersRemindingText, 'inviteOthersRemindingText');
            $inviteOthersRemindingText->save();
            $newSurvey->inviteOthersReminderTextId = $inviteOthersRemindingText->id;
        }

        $newSurvey->save();

        foreach ($survey->categories as $category) {
            $newSurvey->questions()->save($category->replicate());
        }

        foreach ($survey->questions as $question) {
            $newSurvey->questions()->save($question->replicate());
        }

        $recipientLinks = [];
        foreach ($survey->recipients as $recipient) {
            $newRecipient = $recipient->replicate();
            $newRecipient->link = str_random(32);
            $recipientLinks[$newRecipient->invitedById . ':' . $newRecipient->recipientId] = $newRecipient->link;
            $newRecipient->hasAnswered = false;
            $newRecipient->bounced = false;
            $newRecipient->lastReminder = null;
            $newSurvey->recipients()->save($newRecipient);
        }

        foreach ($survey->candidates as $candidate) {
            $newCandidate = $candidate->replicate();
            $newCandidate->link = $recipientLinks[$newCandidate->recipientId . ':' . $newCandidate->recipientId];
            $newCandidate->endDate = $newSurvey->endDate;
            $newCandidate->endDateRecipients = $newSurvey->endDate;
            $newSurvey->candidates()->save($newCandidate);
        }

        foreach ($survey->roleGroups as $roleGroup) {
            $newSurvey->roleGroups()->save($roleGroup->replicate());
        }

        //Send invitations
        $surveyEmailer = $app->app->make('SurveyEmailer');

        if ($newSurvey->type == SurveyTypes::Individual) {
            foreach ($newSurvey->recipients as $recipient) {
                $surveyEmailer->sendSurveyInvitation($newSurvey, $recipient);
            }

            foreach ($newSurvey->candidates as $candidate) {
                $recipient = $candidate->surveyRecipient();
                $surveyEmailer->sendToEvaluate($newSurvey, $recipient, $recipient->link);
            }
        } else if ($newSurvey->type == SurveyTypes::Progress) {
            foreach ($newSurvey->recipients as $recipient) {
                if (!$recipient->isCandidate()) {
                    $surveyEmailer->sendSurveyInvitation($newSurvey, $recipient);
                }
            }

            foreach ($newSurvey->candidates as $candidate) {
                $recipient = $candidate->surveyRecipient();
                $surveyEmailer->sendToEvaluate($newSurvey, $recipient, $recipient->link);
            }
        } else if (SurveyTypes::isGroupLike($newSurvey->type)) {
            foreach ($newSurvey->recipients as $recipient) {
                $surveyEmailer->sendSurveyInvitation($newSurvey, $recipient);
            }
        } else if ($newSurvey->type == SurveyTypes::Normal) {
            foreach ($newSurvey->recipients as $recipient) {
                $surveyEmailer->sendSurveyInvitation($newSurvey, $recipient);
            }
        }

        return $newSurvey;
    }

    /**
     * Creates objects for specific for individual surveys
     */
    private static function createIndividual($app, $survey, $surveyData)
    {
        Surveys::inviteCandidates($app, $survey, $surveyData->individual->candidates);

    }

    /**
    * Creates objects for specific for group surveys
    */
    private static function createGroup($app, $survey, $surveyData)
    {
        $targetGroup = $surveyData->group->targetGroup;
        $roles = $surveyData->group->roles;

        $recipients = [];

        $survey->targetGroupId = $targetGroup->id;
        $survey->save();

        foreach ($roles as $role) {
            $surveyRoleGroup = new \App\Models\SurveyRoleGroup;
            $surveyRoleGroup->roleId = $role->id;
            $surveyRoleGroup->toEvaluate = $role->toEvaluate;
            $survey->roleGroups()->save($surveyRoleGroup);

            foreach ($role->members as $recipient) {
                //Skip inserting duplicate recipients.
                if (!array_key_exists($recipient->id, $recipients)) {
                    Surveys::addGroupMember($app, $survey, $targetGroup->id, $role->id, $recipient);
                    $recipients[$recipient->id] = true;
                }
            }
        }
    }

    /**
     * Creates objects for specific for normal surveys
     */
    private static function createNormal($app, $survey, $surveyData)
    {
        $participants = $surveyData->normal->participants;
        $extraQuestions = $surveyData->normal->extraQuestion;

        Surveys::addParticipantsNormalSurvey($app, $survey, $participants);

        foreach ($extraQuestions as $extraQuestionId) {
            $extraQuestion = new \App\Models\SurveyExtraQuestion;
            $extraQuestion->extraQuestionId = $extraQuestionId;
            $survey->extraQuestions()->save($extraQuestion);
        }
    }
    
    /**
     * Perform specific tasks for instant feedback surveys.
     *
     * @param   App\Models\Survey   $survey
     * @param   array               $recipients
     * @return  void
     */
    private static function createInstant(Survey $survey, array $recipients)
    {
        // Create recipients for each submitted user.
        foreach ($recipients as $recipient) {
            $user = User::find($recipient['id']);
            $recipientObj = Recipient::where([
                'ownerId'   => $survey->ownerId,
                'name'      => $user->name,
                'mail'      => $user->email
            ])->first();
            
            if (!$recipientObj) {
                $recipientObj = new Recipient();
                $recipientObj->ownerId = $survey->ownerId;
                $recipientObj->name = $user->name;
                $recipientObj->mail = $user->email;
                $recipientObj->save();
            }
            
            $survey->addRecipient($recipientObj->id, null, $survey->ownerId);
        }
    }

    /**
    * Invites the given candidates
    */
    public static function inviteCandidates($app, $survey, $candidates)
    {
        $invited = false;
        $surveyEmailer = $app->app->make('SurveyEmailer');

        foreach ($candidates as $candidate) {
            $recipient = !empty($candidate->userId) ? User::find($candidate->userId) : null;
            
            // Make a recipient record if the candidate is not a
            // registered user.
            if (!$recipient) {
                $recipient = \App\Models\Recipient::make(
                    $survey->ownerId,
                    $candidate->name,
                    $candidate->email,
                    $candidate->position);
            }

            //If the recipient already is added as candidate, continue.
            $isAlreadyCandidate = $survey->candidates()
                ->where('surveyId', $survey->id)
                ->where(function ($query) use ($recipient) {
                    $query->where('recipientId', $recipient->id);
                })
                ->first() != null;

            if ($isAlreadyCandidate) {
                continue;
            }

            //Create the recipient
            $surveyRecipient = $survey->addRecipient(
                $recipient->id,
                \App\Roles::selfRoleId(),
                $recipient->id,
                null);

            //Create the candidate
            $surveyInviteRecipient = new \App\Models\SurveyCandidate;
            $surveyInviteRecipient->recipientId = $recipient->id;
            $surveyInviteRecipient->link = $surveyRecipient->link;

            $endDate = $survey->endDate;
            $endDateRecipients = $survey->endDate;

            if ($survey->type == SurveyTypes::Individual) {
                if (isset($candidate->endDate) && $candidate->endDate != null) {
                    $endDate = Surveys::parseEndDate($candidate->endDate);
                    $endDateRecipients = $endDate;
                }
            } else {
                if (isset($candidate->endDate) && $candidate->endDate != null) {
                    $endDate = Surveys::parseEndDate($candidate->endDate);
                }

                if (isset($candidate->endDateRecipients) && $candidate->endDateRecipients != null) {
                    $endDateRecipients = Surveys::parseEndDate($candidate->endDateRecipients);
                }
            }

            $surveyInviteRecipient->endDate = $endDate;
            $surveyInviteRecipient->endDateRecipients = $endDateRecipients;
            $survey->candidates()->save($surveyInviteRecipient);

            //Send the emails
            $surveyEmailer->sendToEvaluate($survey, $surveyRecipient, $surveyRecipient->link);

            // Send notification for registered users
            // if ($userType == 'users') {
                // $surveyRecipient->recipient->notify(new InviteOthersToEvaluate($survey));
            // }

            //Progress only receives one email
            if ($survey->type != \App\SurveyTypes::Progress) {
                $surveyEmailer->sendSurveyInvitation($survey, $surveyRecipient);
            } elseif ($survey->type == SurveyTypes::Individual) {
                $user = User::where('email', $surveyRecipient->recipient->mail)->first();
                if ($user) {
                    $user->notify(new SurveyInvitation($survey, $surveyRecipient->link));
                }
            }

            $invited = true;
        }

        return $invited;
    }

    /**
    * Adds given group member to the survey
    */
    public static function addGroupMember($app, $survey, $groupId, $roleId, $recipient)
    {
        $surveyRecipient = $survey->addRecipient($recipient->id, $roleId, $recipient->id, $groupId);
        $app->app->make('SurveyEmailer')->sendSurveyInvitation($survey, $surveyRecipient);
    }

    /**
    * Adds participants for a normal survey
    */
    public static function addParticipantsNormalSurvey($app, $survey, $participants)
    {
        $invited = false;
        $surveyEmailer = $app->app->make('SurveyEmailer');

        foreach ($participants as $participant) {
            $recipient = \App\Models\Recipient::make(
                $survey->ownerId,
                $participant->name,
                $participant->email,
                '');

            $surveyRecipient = $survey->addRecipient(
                $recipient->id,
                \App\Roles::selfRoleId(),
                $recipient->id);

            $surveyEmailer->sendSurveyInvitation($survey, $surveyRecipient);
            $invited = true;
        }

        return $invited;
    }

    /**
    * Creates the user report link for the given candidate
    */
    public static function createUserReportLink($app, $survey, $candidate, $checkAll = true, $min = 4)
    {
		if (!$survey->createUserReports) {
			return false;
		}

        $exists = $survey->userReports()
            ->where('recipientId', '=', $candidate->recipientId)
            ->count() > 0;

        //Only create if it does not exist
        if (!$exists) {
            $numAnswered = $survey->recipients()
                ->where('invitedById', '=', $candidate->recipientId)
                ->where('recipientId', '!=', $candidate->recipientId)
                ->where('hasAnswered', '=', true)
                ->count();

            $allowed = true;

            //If the deadline has not passed, all must have answered
            if ($checkAll) {
                $numInvited = $candidate
                    ->invited()
                    ->where('recipientId', '!=', $candidate->recipientId)
                    ->count();

                $allowed = $numAnswered === $numInvited;
                $min = 4;
            }

            //Check that atleast min has answered
            if ($numAnswered >= $min && $allowed) {
                $userReport = new \App\Models\SurveyUserReport;
                $userReport->link = str_random(32);
                $userReport->recipientId = $candidate->recipientId;
                $survey->userReports()->save($userReport);

                //Send email
                $app->app->make('SurveyEmailer')->sendUserReportLink(
                    $survey,
                    $candidate->surveyRecipient(),
                    $userReport->link);
            }
        }
    }
}
