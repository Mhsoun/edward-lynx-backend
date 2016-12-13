<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Mail;
use File;
use Log;
use DB;
use App\Models\User;
use App\Models\SurveyQuestion;
use App\Models\Survey;
use App\SurveyTypes;
use App\SurveyEmailer;
use App\SurveyReportHelpers;
use App\Surveys;
use App\Roles;

/**
* Controller for updating surveys
*/
class SurveyUpdateController extends Controller
{
    protected $surveyEmailer;

    /**
     * Create a new controller instance.
     */
    public function __construct(SurveyEmailer $surveyEmailer)
    {
        $this->surveyEmailer = $surveyEmailer;

        Validator::extend('beforeDate', function ($attribute, $value, $parameters) {
            $date = \Carbon\Carbon::parse($value);
            $beforeDate = \Carbon\Carbon::parse($parameters[0]);
            return $date->lt($beforeDate);
        }, 'The :attribute must be before the end date.');

        Validator::extend('afterDate', function ($attribute, $value, $parameters) {
            $date = \Carbon\Carbon::parse($value);
            $afterDate = \Carbon\Carbon::parse($parameters[0]);
            return $date->gt($afterDate);
        }, 'The :attribute must be after the start date.');

        Validator::extend('after_survey_date', function ($attribute, $value, $parameters, $validator) {
            $beforeDate = Surveys::parseStartDate($validator->getData()[$parameters[0]]);
            $afterDate = Surveys::parseEndDate($value);
            return $afterDate->gt($beforeDate);
        }, 'The :attribute must be after the start date.');
    }

    /**
     * Stores the general updates for a survey
     */
    public function updateGeneral(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required',
            'startDate' => 'required|date_format:' . SurveyController::DATE_FORMAT,
            'endDate' => 'required|date_format:' . SurveyController::DATE_FORMAT . '|after_survey_date:startDate',
            'description' => 'required',
            'thankYou' => 'required',
        ]);

        $survey = \App\Models\Survey::findOrFail($id);

        if (SurveyTypes::isIndividualLike($survey->type)) {
            $this->validate($request, [
                'inviteText' => 'required'
            ]);
        }

        $survey->name = $request->name;
        $survey->startDate = Surveys::parseStartDate($request->startDate);
        $survey->endDate = Surveys::parseEndDate($request->endDate);
        $survey->description = $request->description;
        $survey->thankYouText = $request->thankYou;
        $survey->questionInfoText = $request->questionInfo;

        if ($survey->type == \App\SurveyTypes::Progress) {
            $survey->showCategoryTitles = $request->showCategoryTitles == "yes";
        }

        if (SurveyTypes::isIndividualLike($survey->type)) {
            $survey->inviteText = $request->inviteText;
        }

        $survey->save();
        return redirect(action('SurveyController@edit', $id));
    }

    /**
     * Stores the email updates for a survey
     */
    public function updateEmails(Request $request, $id)
    {
        $this->validate($request, [
            'invitationSubject' => 'required',
            'invitationText' => 'required',
            'reminderSubject' => 'required',
            'reminderText' => 'required',
        ]);

        $survey = \App\Models\Survey::findOrFail($id);

        $survey->invitationText->subject = $request->invitationSubject;
        $survey->invitationText->text = $request->invitationText;
        $survey->invitationText->save();

        $survey->manualRemindingText->subject = $request->reminderSubject;
        $survey->manualRemindingText->text = $request->reminderText;
        $survey->manualRemindingText->save();

        if (\App\SurveyTypes::isIndividualLike($survey->type)) {
            $this->validate($request, [
                'toEvaluateInvitationSubject' => 'required',
                'toEvaluateInvitationText' => 'required',
                'inviteOthersReminderSubject' => 'required',
                'inviteOthersReminderText' => 'required',
            ]);

            if ($survey->type != \App\SurveyTypes::Progress) {
                $this->validate($request, [
                    'candidateInvitationSubject' => 'required',
                    'candidateInvitationText' => 'required',
                ]);
            } else {
                $this->validate($request, [
                    'userReportSubject' => 'required',
                    'userReportText' => 'required',
                ]);
            }

            $survey->toEvaluateText->subject = $request->toEvaluateInvitationSubject;
            $survey->toEvaluateText->text = $request->toEvaluateInvitationText;
            $survey->toEvaluateText->save();

            $survey->inviteOthersRemindingText->subject = $request->inviteOthersReminderSubject;
            $survey->inviteOthersRemindingText->text = $request->inviteOthersReminderText;
            $survey->inviteOthersRemindingText->save();

            if ($survey->type != \App\SurveyTypes::Progress) {
                $survey->candidateInvitationText->subject = $request->candidateInvitationSubject;
                $survey->candidateInvitationText->text = $request->candidateInvitationText;
                $survey->candidateInvitationText->save();
            } else {
                $survey->userReportText->subject = $request->userReportSubject;
                $survey->userReportText->text = $request->userReportText;
                $survey->userReportText->save();
            }
        }

        if (\App\SurveyTypes::isGroupLike($survey->type)) {
            $this->validate($request, [
                'toEvaluateTeamInvitationSubject' => 'required',
                'toEvaluateTeamInvitationText' => 'required'
            ]);

            $survey->evaluatedTeamInvitationText->subject = $request->toEvaluateTeamInvitationSubject;
            $survey->evaluatedTeamInvitationText->text = $request->toEvaluateTeamInvitationText;
            $survey->evaluatedTeamInvitationText->save();
        }

        $survey->save();
        Session::flash('activeTab', 'emails');
        return redirect(action('SurveyController@edit', $id));
    }

    /**
    * Updates the participants for the given survey
    */
    public function updateEditParticipants(Request $request, $id)
    {
        $survey = \App\Models\Survey::findOrFail($id);
        $surveyEmailer = app()->app->make('SurveyEmailer');

        foreach ($survey->recipients as $recipient) {
            $newName = $request['recipient_' . $recipient->invitedById . '_' . $recipient->recipientId . '_name'];
            $newEmail = $request['recipient_' . $recipient->invitedById . '_' . $recipient->recipientId . '_email'];

            if ($newName != null && $newName != "") {
                $recipient->recipient->name = $newName;
                $recipient->recipient->save();
            }

            if ($newEmail != $recipient->recipient->mail) {
                //First, check if there exists a recipient with the new email
                $existingRecipient = \App\Models\Recipient::findForOwner($survey->owner, $newEmail);
                if ($existingRecipient != null) {
                    //If that is the case, remove this recipient, and add this one to the survey.
                    $recipient->delete();
                    $recipient = $survey->addRecipient(
                        $existingRecipient->id,
                        $recipient->roleId,
                        $recipient->invitedById,
                        $recipient->groupId);
                } else {
                    $recipient->recipient->mail = $newEmail;
                    $recipient->recipient->save();
                }

                //Send the invitation
                $surveyEmailer->sendSurveyInvitation($survey, $recipient);
            }
        }

        $survey->save();
        Session::flash('activeTab', 'editParticipants');
        return redirect(action('SurveyController@edit', $id));
    }

    /**
    * Updates the role for a recipient in the given survey (AJAX call).
    */
    public function updateChangeRole(Request $request, $id)
    {
        $survey = \App\Models\Survey::findOrFail($id);

        $this->validate($request, [
            'recipientId' => 'required|integer',
            'roleId' => 'required|integer'
        ]);

        if ($survey->type == SurveyTypes::Individual || $survey->type == SurveyTypes::Progress) {
            $this->validate($request, [
                'invitedById' => 'required|integer',
            ]);

            $recipient = $survey->recipients()
                ->where('recipientId', '=', $request->recipientId)
                ->where('invitedById', '=', $request->invitedById)
                ->first();

            if ($recipient != null) {
                if (\App\Roles::valid($request->roleId)) {
                    $recipient->roleId = $request->roleId;
                    $recipient->save();

                    return response()->json([
                        'success' => true
                    ]);
                }
            }
        } else if (SurveyTypes::isGroupLike($survey->type)) {
            $recipient = $survey->recipients()
                ->where('recipientId', '=', $request->recipientId)
                ->first();

            if ($recipient != null) {
                if (\App\Roles::valid($request->roleId)) {
                    $recipient->roleId = $request->roleId;
                    $recipient->save();

                    //Check if the role group needs to be created.
                    $roleId = $request->roleId;
                    if ($survey->roleGroups()->where('roleId', '=', $roleId)->first() == null) {
                        $surveyRoleGroup = new \App\Models\SurveyRoleGroup;
                        $surveyRoleGroup->roleId = $roleId;
                        $surveyRoleGroup->toEvaluate = false;
                        $survey->roleGroups()->save($surveyRoleGroup);
                    }

                    return response()->json([
                        'success' => true
                    ]);
                }
            }
        }

        return response()->json([
            'success' => false
        ]);
    }

    /**
    * Deletes the given participant in the given survey
    */
    public function updateDeleteParticipant(Request $request, $id)
    {
        $survey = \App\Models\Survey::findOrFail($id);
        Session::flash('activeTab', 'editParticipants');

        if ($survey->type == \App\SurveyTypes::Individual || $survey->type == \App\SurveyTypes::Progress) {
            $this->validate($request, [
                'recipientId' => 'required',
                'invitedById' => 'required'
            ]);

            $recipient = $survey->recipients()
                ->where('recipientId', '=', $request->recipientId)
                ->where('invitedById', '=', $request->invitedById)
                ->first();

            if ($recipient != null) {
                //Delete the answers
                $survey->answers()
                    ->where('answeredById', '=', $request->recipientId)
                    ->where('invitedById', '=', $request->invitedById)
                    ->delete();

                //Handle candidate
                if ($request->recipientId == $request->invitedById) {
                    $survey->candidates()
                        ->where('recipientId', '=', $request->recipientId)
                        ->delete();

                    $survey->recipients()
                        ->where('invitedById', '=', $request->recipientId)
                        ->delete();

                    $survey->answers()
                        ->where('invitedById', '=', $request->recipientId)
                        ->delete();

                    $survey->userReports()
                        ->where('recipientId', '=', $request->recipientId)
                        ->delete();
                }

                $recipient->delete();
            }
        } else {
            $this->validate($request, [
                'recipientId' => 'required'
            ]);

            $recipient = $survey->recipients()
                ->where('recipientId', '=', $request->recipientId)
                ->first();

            if ($recipient != null) {
                $survey->answers()
                    ->where('answeredById', '=', $request->recipientId)
                    ->delete();

                $recipient->delete();
            }
        }

        $survey->save();
        return redirect()->back();
    }

    /**
    * Adds a candidate to an existing survey
    */
    public function updateAddCandidate(Request $request, $id)
    {
        $survey = \App\Models\Survey::findOrFail($id);

        if (!($survey->type == SurveyTypes::Individual || $survey->type == SurveyTypes::Progress)) {
            return redirect(action('SurveyController@index'));
        }

        $isNewProgress = SurveyTypes::isNewProgress($survey);
        $is360 = $survey->type == SurveyTypes::Individual;
        $candidate = null;

        if ($request->name != null) {
            if ($is360) {
                $this->validate($request, [
                    'name' => 'required',
                    'email' => 'required',
                    'newEndDate' => 'required|date_format:' . SurveyController::DATE_FORMAT,
                ]);

                $candidate = (object)[
                    'name' => $request->name,
                    'email' => $request->email,
                    'position' => $request->position ?: "",
                    'endDate' => $request->newEndDate,
                ];
            } else if ($isNewProgress) {
                $this->validate($request, [
                    'name' => 'required',
                    'email' => 'required',
                    'newEndDate' => 'required|date_format:' . SurveyController::DATE_FORMAT,
                    'newEndDateRecipients' => 'required|date_format:' . SurveyController::DATE_FORMAT,
                ]);

                $candidate = (object)[
                    'name' => $request->name,
                    'email' => $request->email,
                    'position' => $request->position ?: "",
                    'endDate' => $request->newEndDate,
                    'endDateRecipients' => $request->newEndDateRecipients,
                ];
            } else {
                $this->validate($request, [
                    'name' => 'required',
                    'email' => 'required',
                ]);

                $candidate = (object)[
                    'name' => $request->name,
                    'email' => $request->email,
                    'position' => $request->position ?: "",
                ];
            }
        } else {
            $this->validate($request, [
                'existingRecipientId' => 'required|integer'
            ]);

            $recipient = \App\Models\Recipient::
                where('ownerId', '=', $survey->ownerId)
                ->where('id', '=', $request->existingRecipientId)
                ->first();

            if ($recipient == null) {
                return redirect(action('SurveyController@edit', $id));
            } else {
                $candidate = (object)[
                    'name' => $recipient->name,
                    'email' => $recipient->mail,
                    'position' => $recipient->position,
                ];
            }

            if ($isNewProgress) {
                $this->validate($request, [
                    'existingEndDate' => 'required|date_format:' . SurveyController::DATE_FORMAT,
                    'existingEndDateRecipients' => 'required|date_format:' . SurveyController::DATE_FORMAT,
                ]);

                $candidate->endDate = $request->existingEndDate;
                $candidate->endDateRecipients = $request->existingEndDateRecipients;
            } else if ($is360) {
                $this->validate($request, [
                    'existingEndDate' => 'required|date_format:' . SurveyController::DATE_FORMAT,
                ]);

                $candidate->endDate = $request->existingEndDate;
                $candidate->endDateRecipients = $request->existingEndDate;
            }
        }

        if (Surveys::inviteCandidates(app(), $survey, [$candidate])) {
            Session::flash('changeText', Lang::get('surveys.candidateInvited'));
        } else {
            Session::flash('changeText', Lang::get('surveys.alreadyInvited'));
        }

        Session::flash('activeTab', 'candidates');
        return redirect(action('SurveyController@edit', $id));
    }

    /**
    * Adds participants to an existing survey
    */
    public function updateAddParticipants(Request $request, $id)
    {
        $survey = \App\Models\Survey::findOrFail($id);

        if (!SurveyTypes::isGroupLike($survey->type)) {
            return redirect(action('SurveyController@index'));
        }

        if ($request->newParticipants != null) {
            foreach ($request->newParticipants as $recipientId) {
                $groupMember = $survey->targetGroup
                    ->members()
                    ->where('memberId', '=', $recipientId)
                    ->first();

                if ($groupMember != null && $survey->recipients()->where('recipientId', '=', $recipientId)->first() == null) {
                    //Check if the role group needs to be created.
                    $roleId = $groupMember->roleId;
                    if ($survey->roleGroups()->where('roleId', '=', $roleId)->first() == null) {
                        $surveyRoleGroup = new \App\Models\SurveyRoleGroup;
                        $surveyRoleGroup->roleId = $roleId;
                        $surveyRoleGroup->toEvaluate = false;
                        $survey->roleGroups()->save($surveyRoleGroup);
                    }

                    Surveys::addGroupMember(
                        app(),
                        $survey,
                        $survey->targetGroupId,
                        $roleId,
                        $groupMember->recipient);

                    Session::flash('changeText', Lang::get('surveys.participantsInvited'));
                }
            }
        }

        return redirect(action('SurveyController@edit', $id));
    }

    /**
    * Adds participant to an existing survey
    */
    public function updateAddParticipant(Request $request, $id)
    {
        $survey = \App\Models\Survey::findOrFail($id);
        Session::flash('activeTab', 'participants');

        if ($survey->type != \App\SurveyTypes::Normal) {
            return redirect(action('SurveyController@index'));
        }

        $candidate = null;

        if ($request->name != null) {
            $this->validate($request, [
                'name' => 'required',
                'email' => 'required',
            ]);

            $candidate = (object)[
                'name' => $request->name,
                'email' => $request->email,
                'position' => $request->position ?: "",
            ];
        } else {
            $this->validate($request, [
                'existingRecipientId' => 'required|integer'
            ]);

            $recipient = \App\Models\Recipient::
                where('ownerId', '=', $survey->ownerId)
                ->where('id', '=', $request->existingRecipientId)
                ->first();

            if ($recipient == null) {
                return redirect(action('SurveyController@edit', $id));
            } else {
                $candidate = (object)[
                    'name' => $recipient->name,
                    'email' => $recipient->mail,
                    'position' => $recipient->position,
                ];
            }
        }

        if (Surveys::addParticipantsNormalSurvey(app(), $survey, [$candidate])) {
            Session::flash('changeText', Lang::get('surveys.participantInvited'));
        } else {
            Session::flash('changeText', Lang::get('surveys.alreadyInvited'));
        }

        return redirect(action('SurveyController@edit', $id));
    }

	/**
    * Sets the end date for a candidate
    */
    public function updateSetCandidateEndDate(Request $request, $id)
    {
        $survey = \App\Models\Survey::findOrFail($id);

        $isNewProgress = \App\SurveyTypes::isNewProgress($survey);
        $is360 = $survey->type == \App\SurveyTypes::Individual;

        if (!($isNewProgress || $is360)) {
            return redirect(action('SurveyController@index'));
        }

        if ($isNewProgress) {
    		$this->validate($request, [
    			'candidateId' => 'required',
    			'endDate' => 'required|date_format:' . SurveyController::DATE_FORMAT,
    			'endDateRecipients' => 'required|date_format:' . SurveyController::DATE_FORMAT,
    		]);
        } else if ($is360) {
            $this->validate($request, [
                'candidateId' => 'required',
                'endDate' => 'required|date_format:' . SurveyController::DATE_FORMAT,
            ]);
        }

		$candidate = $survey->candidates()
			->where('recipientId', '=', $request->candidateId)
			->first();

		if ($candidate != null) {
            if ($isNewProgress) {
	             $candidate->endDate = \App\Surveys::parseEndDate($request->endDate);
		         $candidate->endDateRecipients = \App\Surveys::parseEndDate($request->endDateRecipients);
            } else if ($is360) {
                 $candidate->endDate = \App\Surveys::parseEndDate($request->endDate);
                 $candidate->endDateRecipients = \App\Surveys::parseEndDate($request->endDate);
            }

			$candidate->save();
			return redirect(action('SurveyController@showCandidate', ['id' => $survey->id, 'candidateId' => $candidate->recipientId]));
		} else {
			return redirect(action('SurveyController@index'));
		}
	}

    /**
    * Creates a user report for a cnadidate
    */
    public function createUserReport(Request $request, $id)
    {
        $survey = \App\Models\Survey::findOrFail($id);

        if (!\App\SurveyTypes::isNewProgress($survey)) {
            return redirect(action('SurveyController@index'));
        }

		$candidate = $survey->candidates()
			->where('recipientId', '=', $request->candidateId)
			->first();

		if ($candidate != null) {
            \App\Surveys::createUserReportLink(app(), $survey, $candidate, false, 1);
			return redirect(action('SurveyController@showCandidate', ['id' => $survey->id, 'candidateId' => $candidate->recipientId]));
		} else {
			return redirect(action('SurveyController@index'));
		}
	}

    /**
    * Creates tags for the given question
    */
    private function createTags($question, $tags)
    {
        foreach ($tags as $tag) {
            $questionTag = new \App\Models\QuestionTag;
            $questionTag->tag = $tag;
            $question->tags()->save($questionTag);
        }
    }

    /**
    * Adds a question to an existing survey
    */
    public function updateAddQuestion(Request $request, $id)
    {
        $survey = \App\Models\Survey::findOrFail($id);
        Session::flash('activeTab', 'questions');

        $this->validate($request, [
            'questionScale' => 'required|integer',
            'categoryId' => 'required|integer',
            'questionText' => 'required'
        ]);

        $surveyQuestion = SurveyQuestion::make(
            $survey,
            $request->categoryId,
            $request->questionText,
            $request->questionScale,
            $request->questionOptional == "yes",
            $request->questionIsNA == "yes",
            $request->questionCustomValues ?: []);

        if ($request->questionTags != null) {
            $this->createTags($surveyQuestion->question, explode(";", $request->questionTags));
        }

        if (SurveyTypes::isGroupLike($survey->type)) {
            $surveyQuestion->addTargetRoles(Roles::getLMTT()->map(function ($role) {
                return $role->id;
            }));
        }

        $surveyQuestion->question->save();
        return redirect(action('SurveyController@edit', $id));
    }

    /**
    * Updates the order for a question in a survey (AJAX call).
    */
    public function updateQuestionOrder(Request $request, $id)
    {
        $this->validate($request, [
            'questionId' => 'required|integer',
            'order' => 'required|integer'
        ]);

        $survey = \App\Models\Survey::findOrFail($id);

        $question = $survey->questions()
            ->where('questionId', '=', $request->questionId)
            ->first();

        if ($question != null) {
            DB::table('survey_questions')
                ->where('surveyId', $survey->id)
                ->where('questionId', $request->questionId)
                ->update(['order' => $request->order]);

            return response()->json(['success' => true]);
        } else {
            return response()->json(['success' => false]);
        }
    }

    /**
    * Updates the order for a category in a survey (AJAX call).
    */
    public function updateCategoryOrder(Request $request, $id)
    {
        $this->validate($request, [
            'categoryId' => 'required|integer',
            'order' => 'required|integer'
        ]);

        $survey = \App\Models\Survey::findOrFail($id);

        $category = $survey->categories()
            ->where('categoryId', '=', $request->categoryId)
            ->first();

        if ($category != null) {
            //We use DB here since Laravel requires primary key for the save methods
            DB::table('survey_question_categories')
                ->where('surveyId', $survey->id)
                ->where('categoryId', $request->categoryId)
                ->update(['order' => $request->order]);

            return response()->json(['success' => true]);
        } else {
            return response()->json(['success' => false]);
        }
    }

    /**
    * Adds a category to an existing survey
    */
    public function updateAddCategory(Request $request, $id)
    {
        $survey = \App\Models\Survey::findOrFail($id);
        Session::flash('activeTab', 'questions');

        $this->validate($request, [
            'categoryTitle' => 'required'
        ]);

        if (\App\Models\QuestionCategory::exists($survey->ownerId, $request->categoryTitle, $survey->type, $survey->lang)) {
            return redirect(action('SurveyController@edit', $id))
                ->withErrors(['categoryExists' =>Lang::get('surveys.categoryExists')]);
        }

        $category = new \App\Models\QuestionCategory;
        $category->title = $request->categoryTitle;
        $category->description = $request->categoryDescription ?: "";
        $category->ownerId = $survey->ownerId;
        $category->lang = $survey->lang;
        $category->targetSurveyType = $survey->type;
        $category->isSurvey = true;
        $category->save();

        $surveyCategory = new \App\Models\SurveyQuestionCategory;
        $surveyCategory->categoryId = $category->id;
        $surveyCategory->order = $survey->categories()->max('order') + 1;
        $survey->categories()->save($surveyCategory);

        return redirect(action('SurveyController@edit', $id));
    }

    /**
    * Adds an existing category to an existing survey
    */
    public function updateAddExistingCategory(Request $request, $id)
    {
        $survey = \App\Models\Survey::findOrFail($id);
        Session::flash('activeTab', 'questions');

        $this->validate($request, [
            'existingCategoryId' => 'required'
        ]);

        $category = \App\Models\QuestionCategory::
            where('ownerId', '=', $survey->ownerId)
            ->where('id', '=', $request->existingCategoryId)
            ->first();

        if ($category == null) {
            return redirect(action('SurveyController@edit', $id));
        }

        $surveyCategory = new \App\Models\SurveyQuestionCategory;
        $surveyCategory->categoryId = $category->copy()->id;
        $surveyCategory->order = $survey->categories()->max('order') + 1;
        $survey->categories()->save($surveyCategory);

        return redirect(action('SurveyController@edit', $id));
    }

    public function updateChangeCategoryTitle(Request $request, $id)
    {
        $survey = \App\Models\Survey::findOrFail($id);
        Session::flash('activeTab', 'questions');

        $this->validate($request, [
            'updateCategoryId' => 'required',
            'updateCategoryTitle' => 'required'
        ]);

        $category = $survey->categories()
            ->where('categoryId', '=', $request->updateCategoryId)
            ->first();

        if ($category != null) {
            $category->category->title = $request->updateCategoryTitle;
            $category->category->save();
        }

        return redirect(action('SurveyController@edit', $id));
    }

    /**
    * Removes a question from a survey (AJAX call).
    */
    public function updateDeleteQuestion(Request $request, $id)
    {
        $survey = \App\Models\Survey::findOrFail($id);

        $this->validate($request, [
            'questionId' => 'required|integer'
        ]);

        $question = $survey->questions()
            ->where('questionId', '=', $request->questionId)
            ->first();

        if ($question != null) {
            \App\Models\SurveyQuestion::
                where('surveyId', '=', $survey->id)
                ->where('questionId', '=', $question->questionId)
                ->delete();

            $survey->answers()
                ->where('questionId', '=', $question->questionId)
                ->delete();

            return response()->json(['success' => true]);
        } else {
            return response()->json(['success' => false]);
        }
    }

    /**
     * Sets the report template for a survey.
     */
    public function updateSetReportTemplate(Request $request, $id)
    {
        $survey = \App\Models\Survey::findOrFail($id);
        Session::flash('activeTab', 'reportTemplate');

        if ($request->activeReportTemplateId != null) {
            $activeReportTemplateId = $request->activeReportTemplateId;

            if (\App\Models\ReportTemplate::find($activeReportTemplateId) != null) {
                $survey->activeReportTemplateId = $activeReportTemplateId;
            }
        } else {
            $survey->activeReportTemplateId = null;
        }

        $survey->save();
        return redirect(action('SurveyController@edit', $id));
    }

    /**
    * Removes answers for a recipient in a survey.
    */
    public function updateDeleteAnswers(Request $request, $id)
    {
        $survey = \App\Models\Survey::findOrFail($id);

        $this->validate($request, [
            'candidateId' => 'required|integer',
            'recipientId' => 'required|integer'
        ]);

        $recipient = $survey->recipients()
            ->where('invitedById', '=', $request->candidateId)
            ->where('recipientId', '=', $request->recipientId)
            ->first();

        if ($recipient != null) {
            $recipient->hasAnswered = false;
            $recipient->save();

            $survey->answers()
                ->where('invitedById', '=', $request->candidateId)
                ->where('answeredById', '=', $request->recipientId)
                ->delete();
        }

        return redirect(action('SurveyController@show', ['id' => $id]));
    }

    /**
     * Updates the automatic reminders for the given survey.
     */
    public function updateAutoReminder(Request $request, $id)
    {
        $enableAutoReminding = false;

        if ($request->enableAutoReminding == 'on') {
            $enableAutoReminding = true;
        }

        $survey = \App\Models\Survey::findOrFail($id);

        $survey->enableAutoReminding = $enableAutoReminding;

        if ($enableAutoReminding) {
            $this->validate($request, [
                'autoRemindingDate' =>
                    'required|date_format:' . $dateFormat = 'Y-m-d H:i'
                        . '|beforeDate:' . $survey->endDate
                        . '|afterDate:' . $survey->startDate
            ]);

            $survey->autoRemindingDate = \Carbon\Carbon::parse($request->autoRemindingDate);
        }

        $survey->save();
        return redirect(action('SurveyController@show', $survey->id));
    }
}
