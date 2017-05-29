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
use App\Roles;
use App\SurveyTypes;
use App\SurveyEmailer;
use App\Models\Survey;
use App\Models\User;
use App\SurveyReportHelpers;
use App\Surveys;

/**
* Controller for creating and viewing surveys
*/
class SurveyController extends Controller
{
    //The date format used in survey dates
    const DATE_FORMAT = 'Y-m-d';
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
    * Returns the company id for the given request
    */
    private function getCompanyId(Request $request)
    {
       if (Auth::user()->isAdmin && $request->companyId != null) {
            return $request->companyId;
        } else {
            return Auth::user()->id;
        }
    }

    /**
    * View for when the survey was not found
    */
    public function notFound()
    {
        return view('survey.notFound');
    }

    /**
     * Shows the list of surveys for the auth user
     */
    public function index()
    {
        $isEdwardLynx = false;
        $users = null;

        if (Auth::user()->isAdmin) {
            $surveys = \App\Models\Survey::all();
            $isEdwardLynx = true;
            $users = \App\Models\User::all();
        } else {
            $surveys = \App\Models\Survey::where('ownerId', '=', Auth::user()->id)->get();
        }

        $activeSurveys = [];
        $finishedSurveys = [];
        $timeNow = \Carbon\Carbon::now(Survey::TIMEZONE);

        foreach ($surveys as $survey) {
            $survey->numCompleted = $survey->recipients()
                ->where('hasAnswered', '=', true)
                ->count();

            if ($timeNow->gt($survey->endDate)) {
                array_push($finishedSurveys, $survey);
            } else {
                array_push($activeSurveys, $survey);
            }
        }

        return view('survey.index', compact('activeSurveys', 'finishedSurveys', 'isEdwardLynx', 'users'));
    }

    /**
    * Returns the create view
    */
    private function createView($companyId)
    {
        $recipients = \App\Models\Recipient::where('ownerId', '=', $companyId)->get();

        $groups = array_sort(\App\Models\Group::where('ownerId', '=', $companyId)->get(), function($group)
        {
            return $group->fullName();
        });

        $categories = \App\Models\QuestionCategory::where('ownerId', '=', $companyId)->get();
        return view('survey.create', compact('recipients', 'groups', 'categories', 'companyId'));
    }

    /**
     * Returns the create view
     */
    public function create()
    {
        if (User::isEdwardLynx(Auth::user()->id)) {
            $users = User::companies()->get();
            return view('survey.selectCompany', compact('users'));
        } else {
            return $this->createView(Auth::user()->id);
        }
    }

    /**
     * Returns the create view for a company
     */
    public function createCompany($companyId)
    {
        //Check if the company exists
        if (\App\Models\User::find($companyId) == null) {
            return redirect(action('SurveyController@create'));
        }

        return $this->createView($companyId);
    }

    /**
     * Returns the create view for company by looking for the company by name
     */
    public function createCompanyByName(Request $request)
    {
        $this->validate($request, [
            'company' => 'required'
        ]);

        //Find the company
        $company = \App\Models\User::where('name', '=', $request->company)
            ->get()
            ->first();

        if ($company == null) {
            return redirect(action('SurveyController@create'));
        }
        return $this->createView($company->id);
    }

    /**
     * Returns the type
     */
    private function getSurveyType($type)
    {
        switch ($type) {
            case 'individual':
                return SurveyTypes::Individual;
            case 'group':
                return SurveyTypes::Group;
            case 'progress':
                return SurveyTypes::Progress;
            case 'normal':
                return SurveyTypes::Normal;
            case 'ltt':
                return SurveyTypes::LTT;
            default:
                return -1;
        }
    }

    /**
    * Returns the candidates from the given request
    */
    private function getCandidates($names, $emails, $positions, $endDates, $endDatesRecipients)
    {
        $usedEmails = [];
        $participants = [];

        $numNames = count($names);
        $numEmails = count($emails);
        $numPositions = count($positions);

        if (!(($numNames & $numEmails & $numPositions) == $numNames)) {
            return $participants;
        }

        for ($i = 0; $i < $numNames; $i++) {
            $email = $emails[$i];
            $endDate = null;
            $endDateRecipients = null;

            if ($endDates != null && array_key_exists($i, $endDates)) {
                $endDate = $endDates[$i];
            }

            if ($endDatesRecipients != null && array_key_exists($i, $endDatesRecipients)) {
                $endDateRecipients = $endDatesRecipients[$i];
            }

            if (!array_key_exists($email, $usedEmails)) {
                array_push($participants, (object)[
                    'name' => $names[$i],
                    'email' => $email,
                    'position' => $positions[$i],
                    'endDate' => $endDate,
                    'endDateRecipients' => $endDateRecipients
                ]);

                $usedEmails[$email] = true;
            }
        }

        return $participants;
    }

    /**
    * Returns the participants from the given request
    */
    private function getNormalParticipants($request)
    {
        $names = $request->normalParticipantNames;
        $emails = $request->normalParticipantEmails;

        $usedEmails = [];
        $participants = [];

        $numNames = count($names);
        $numEmails = count($emails);

        if (!$numNames == $numEmails) {
            return $participants;
        }

        for ($i = 0; $i < $numNames; $i++) {
            $email = $emails[$i];

            if (!array_key_exists($email, $usedEmails)) {
                array_push($participants, (object)[
                    'name' => $names[$i],
                    'email' => $email
                ]);

                $usedEmails[$email] = true;
            }
        }

        return $participants;
    }

    /**
     * Returns the roles from the given request
     */
    private function getRoles($targetGroup, $request)
    {
        $roles = [];
        $toEvaluateRoleId = $request->toEvaluateRole;

        if (!\App\Roles::valid($toEvaluateRoleId)) {
            $toEvaluateRoleId = \App\Models\RoleName::where('name', '=', Lang::get('roles.manager', [], 'en'))
                ->first()
                ->roleId;
        }

        foreach ($request->includedMembers as $member) {
            $groupMember = $targetGroup->members()
                ->where('memberId', '=', $member)
                ->first();

            if ($groupMember != null) {
                $role = null;
                $roleId = $groupMember->roleId;

                if (array_key_exists($roleId, $roles)) {
                    $role = $roles[$roleId];
                } else {
                    $role = (object)[
                        'id' => $roleId,
                        'members' => [],
                        'toEvaluate' => $roleId == $toEvaluateRoleId
                    ];

                    $roles[$roleId] = $role;
                }

                array_push($role->members, $groupMember->recipient);
            }
        }

        return array_values($roles);
    }

    /**
     * Returns the categories and question
     */
    private function getCategoriesAndQuestions($questionIds, $questionTargetRoleIds, $categoryIds)
    {
        $questions = [];
        $categories = [];

        //Create the category order
        $categoryOrder = [];
        for ($i = 0; $i < count($categoryIds); $i++) {
            $categoryOrder[$categoryIds[$i]] = $i;
        }

        //Create the question target groups
        $questionTargetRoles = [];
        if ($questionTargetRoleIds != null) {
            for ($i = 0; $i < count($questionTargetRoleIds); $i++) {
                $questionTargetRoles[$questionIds[$i]] = explode(";", $questionTargetRoleIds[$i]);
            }
        }

        $questionOrder = [];
        if ($questionIds != null) {
            foreach ($questionIds as $questionId) {
                $question = \App\Models\Question::find($questionId);

                if ($question != null) {
                    $category = $question->category;
                    $order = 0;
                    $targetRoles = [];

                    if (array_key_exists($category->id, $questionOrder)) {
                        $order = $questionOrder[$category->id];
                    }

                    if (array_key_exists($question->id, $questionTargetRoles)) {
                        $targetRoles = $questionTargetRoles[$question->id];
                    }

                    array_push($questions, (object)[
                        'id' => $question->id,
                        'order' => $order,
                        'targetRoles' => $targetRoles
                    ]);

                    $questionOrder[$category->id] = $order + 1;

                    if (!array_key_exists($category->id, $categories)) {
                        $categories[$category->id] = (object)[
                            'id' => $category->id,
                            'order' => $categoryOrder[$category->id]
                        ];
                    }
                }
            }
        }

        return [
            'categories' => $categories,
            'questions' => $questions
        ];
    }

    /**
    * Validates the given create survey data
    */
    private function validateCreateSurveyData($request, $type)
    {
        $validations = [
            'name' => 'required',
            'language' => 'required',
            'type' => 'required',
            'startDate' => 'required',
            'endDate' => 'required',
            'description' => 'required',
            'thankYou' => 'required',
            'invitationSubject' => 'required',
            'invitationText' => 'required',
            'reminderSubject' => 'required',
            'reminderText' => 'required',
        ];

        //First, vaildate data.
        if (\App\SurveyTypes::isIndividualLike($type)) {
            $validations = array_merge($validations, [
                'toEvaluateInvitationSubject' => 'required',
                'toEvaluateInvitationText' => 'required',
                'individualInviteText' => 'required',
                'inviteOthersReminderSubject' => 'required',
                'inviteOthersReminderText' => 'required'
            ]);

            if ($type == SurveyTypes::Individual) {
                $validations = array_merge($validations, [
                    'candidateInvitationSubject' => 'required',
                    'candidateInvitationText' => 'required',
                ]);
            } else if ($type == SurveyTypes::Progress) {
                $validations = array_merge($validations, [
                    'userReportSubject' => 'required',
                    'userReportText' => 'required',
                ]);
            }
        } else if (\App\SurveyTypes::isGroupLike($type)) {
            $validations = array_merge($validations, [
                'targetGroupId' => 'required|integer',
                'toEvaluateTeamInvitationSubject' => 'required',
                'toEvaluateTeamInvitationText' => 'required'
            ]);
        }

        $this->validate($request, $validations);
    }

    /**
    * Returns the data required to create a survey from the given request.
    */
    private function getCreateSurveyData($request)
    {
        $ownerId = $this->getCompanyId($request);

        $type = $this->getSurveyType($request->type);
        if ($type == -1) {
            return null;
        }

        //This only matters if the user is not an admin
        if (!Auth::user()->isAdmin) {
            if (!SurveyTypes::canCreate(Auth::user()->allowedSurveyTypes, $type)) {
                return null;
            }
        }

        //Validate
        $this->validateCreateSurveyData($request, $type);

        $startDate = Surveys::parseStartDate($request->startDate);
        $endDate = Surveys::parseEndDate($request->endDate);

        if ($startDate->gt($endDate)) {
            return null;
        }

        //Create the base survey data
        $survey = (object)[
            'name' => $request->name,
            'type' => $type,
            'lang' => $request->language,
            'ownerId' => $ownerId,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'description' => $request->description,
            'thankYou' => $request->thankYou,
            'questionInfo' => $request->questionInfo ?: "",
            'emails' => (object)[
                'invitation' => (object)[
                    'subject' => $request->invitationSubject,
                    'text' => $request->invitationText
                ],
                'reminder' => (object)[
                    'subject' => $request->reminderSubject,
                    'text' => $request->reminderText
                ],
            ]
        ];

        //Categories and questions
        $questionAndCategories = $this->getCategoriesAndQuestions(
            $request->questions,
            $request->questionTargetRoles,
            $request->categories);

        $survey->categories = $questionAndCategories['categories'];
        $survey->questions = $questionAndCategories['questions'];

        //Now, type specific data
        if (\App\SurveyTypes::isIndividualLike($type)) {
            $candidates = $this->getCandidates(
                $request->candidateNames,
                $request->candidateEmails,
                $request->candidatePositions,
                $request->candidateEndDates,
                $request->candidateEndDatesRecipients);

            if (count($candidates) == 0) {
                return null;
            }

            $survey->individual = (object)[
                'candidates' => $candidates,
                'inviteText' => $request->individualInviteText
            ];

            $survey->emails->toEvaluate = (object)[
                'subject' => $request->toEvaluateInvitationSubject,
                'text' => $request->toEvaluateInvitationText,
            ];

            $survey->emails->inviteOthersReminder = (object)[
                'subject' => $request->inviteOthersReminderSubject,
                'text' => $request->inviteOthersReminderText,
            ];

            if ($type == SurveyTypes::Individual) {
                $survey->emails->candidateInvite = (object)[
                    'subject' => $request->candidateInvitationSubject,
                    'text' => $request->candidateInvitationText,
                ];
            } else if ($type == SurveyTypes::Progress) {
                $survey->emails->userReport = (object)[
                    'subject' => $request->userReportSubject,
                    'text' => $request->userReportText,
                ];
            }
        } else if (\App\SurveyTypes::isGroupLike($type)) {
            $targetGroup = \App\Models\Group::find($request->targetGroupId);
            $roles = $this->getRoles($targetGroup, $request);

            if ($targetGroup == null || count($roles) < 2) {
                return null;
            }

            $survey->group = (object)[
                'targetGroup' => $targetGroup,
                'roles' => $roles
            ];

            $survey->emails->toEvaluateRole = (object)[
                'subject' => $request->toEvaluateTeamInvitationSubject,
                'text' => $request->toEvaluateTeamInvitationText,
            ];
        } else if ($type == \App\SurveyTypes::Normal) {
            $participants = $this->getNormalParticipants($request);

            if (count($participants) == 0) {
                return null;
            }

            $survey->normal = (object)[
                'participants' => $participants
            ];

            $extraQuestionIds = [];

            if ($request->extraQuestions != null) {
                foreach ($request->extraQuestions as $extraQuestion) {
                    if (\App\Models\ExtraQuestion::where('id', '=', $extraQuestion)->count() > 0) {
                        $extraQuestionIds[$extraQuestion] = $extraQuestion;
                    }
                }
            }

            $survey->normal->extraQuestion = array_values($extraQuestionIds);
        }

        return $survey;
    }

    /**
     * Stores a created survey
     */
    public function store(Request $request)
    {
        $surveyData = $this->getCreateSurveyData($request);

        if ($surveyData == null) {
            return redirect(action('SurveyController@create'));
        }

        $survey = Surveys::create(app(), $surveyData);
        return view('survey.created', compact('survey'));
    }

    /**
    * Imports recipients from a CSV file
    */
    public function importRecipientsFromCSV(Request $request)
    {
        $this->validate($request, [
            'csv' => 'required'
        ]);

        $ignorePosition = false;
        if ($request->ignorePosition != null) {
            $ignorePosition = $request->ignorePosition;
        }

        $imported = [];
        $addedEmails = [];

        //Parse and create members
        foreach (\App\CSVParser::parse($request->csv, !$ignorePosition ? 3 : 2) as $user) {
            $validator = Validator::make(['email' => $user[1]], [
                'email' => 'email'
            ]);

            if (!$validator->fails() && !array_key_exists($user[1], $addedEmails)) {
                if (!$ignorePosition) {
                    array_push($imported, (object)[
                        'name' => $user[0],
                        'email' => $user[1],
                        'position' => $user[2]
                    ]);
                } else {
                    array_push($imported, (object)[
                        'name' => $user[0],
                        'email' => $user[1]
                    ]);
                }

                $addedEmails[$user[1]] = true;
            }
        }

        return response()->json([
            'success' => true,
            'imported' => $imported
        ]);
    }

    /**
    * Returns the report path
    */
    private function reportPath($fileName = null)
    {
        $reportDir = public_path() . DIRECTORY_SEPARATOR . 'reports';

        if ($fileName != null) {
            return $reportDir . DIRECTORY_SEPARATOR . $fileName;
        } else {
            return $reportDir;
        }
    }

    /**
     * Deletes the given survey
     */
    public function destroy(Request $request, $id)
    {
        $survey = \App\Models\Survey::findOrFail($id);

        //Delete the reports
        foreach ($survey->reports as $report) {
            File::delete($this->reportPath($report->fileName));
        }

        $survey->delete();

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Returns the edit view for the given survey
     */
    public function edit(Request $request, $id)
    {
        $survey = \App\Models\Survey::find($id);
        $ownerRecipients = [];
        $notIncludedMembers = [];

        if (SurveyTypes::isGroupLike($survey->type)) {
            $notIncludedMembers = $survey->targetGroup
                ->members()
                ->whereRaw('memberId NOT IN (SELECT recipientId FROM survey_recipients WHERE surveyId=?)', [$survey->id])
                ->get();

            $ownerRecipients = \App\Models\Recipient::
               where('ownerId', '=', $survey->ownerId)
               ->whereRaw('id NOT IN (
                          SELECT recipientId FROM survey_recipients WHERE surveyId=?
                          UNION ALL
                          SELECT memberId FROM group_members WHERE `groupId`=?)', [$survey->id, $survey->targetGroupId])
               ->get();
        } else if ($survey->type == SurveyTypes::Individual || $survey->type == SurveyTypes::Progress) {
            $ownerRecipients = \App\Models\Recipient::
                where('ownerId', '=', $survey->ownerId)
                ->whereRaw('id NOT IN (SELECT recipientId FROM survey_candidates WHERE surveyId=?)', [$survey->id])
                ->get();
        } else if ($survey->type == SurveyTypes::Normal) {
            $ownerRecipients = \App\Models\Recipient::
                where('ownerId', '=', $survey->ownerId)
                ->whereRaw('id NOT IN (SELECT recipientId FROM survey_recipients WHERE surveyId=?)', [$survey->id])
                ->get();
        }

        if ($request->activeTab != null) {
            Session::flash('activeTab', $request->activeTab);
        }

        return view('survey.edit', compact('survey', 'ownerRecipients', 'notIncludedMembers'));
    }

    /**
     * Sends a reminding mail to the given recipient
     */
    public function sendReminder($survey, $recipient)
    {
        $this->surveyEmailer->sendReminder($survey, $recipient);
        $recipient->lastReminder = \Carbon\Carbon::now();
        $recipient->save();
    }

    /**
     * Sends reminders for the given survey
     */
    public function sendReminders(Request $request, $id)
    {
        $survey = \App\Models\Survey::findOrFail($id);

        if ($request->recipients != null) {
            foreach ($request->recipients as $currentRecipient) {
                $splitedRecipient = explode(':', $currentRecipient);
                $invitedById = $splitedRecipient[0];
                $recipientId = $splitedRecipient[1];

                $recipient = $survey->recipients()
                    ->where('invitedById', '=', $invitedById)
                    ->where('recipientId', '=', $recipientId)
                    ->get()
                    ->first();

                if ($recipient != null && !$recipient->hasAnswered) {
                    $this->sendReminder($survey, $recipient);
                }
            }
        }

        return redirect()->back();
    }

    /**
     * Sends an invite others reminding mail to the given candidate
     */
    public function sendInviteOtherReminder($survey, $candidate)
    {
        $this->surveyEmailer->sendInviteOtherReminder($survey, $candidate->surveyRecipient(), $candidate->link);
    }

    /**
     * Sends invite others reminders for the given survey
     */
    public function sendInviteOthersReminders(Request $request, $id)
    {
        $survey = \App\Models\Survey::findOrFail($id);

        if ($request->candidateReminderIds != null) {
            foreach ($request->candidateReminderIds as $candidateId) {
                $candidate = $survey->candidates()
                    ->where('recipientId', '=', $candidateId)
                    ->first();

                if ($candidate != null) {
                    $this->sendInviteOtherReminder($survey, $candidate);
                }
            }
        }

        return redirect()->back();
    }

    /**
     * Returns the show view for the given survey
     */
    public function show(Request $request, $id)
    {
        $survey = \App\Models\Survey::findOrFail($id);
        return view('survey.view', compact('survey'));
    }

    /**
     * Returns the answers for the given survey and recipient
     */
    public function showAnswers(Request $request, $id)
    {
        $survey = \App\Models\Survey::findOrFail($id);
        $answers = null;

        if (\App\SurveyTypes::isIndividualLike($survey->type)) {
            $candidateId = $request['candidateId'];
            $recipientId = $request['recipientId'];
            $recipient = $survey->recipients()
                ->where('recipientId', '=', $recipientId)
                ->where('invitedById', '=', $candidateId)
                ->first();

            $answers = $survey->answers()
                ->where('answeredById', '=', $recipientId)
                ->where('invitedById', '=', $candidateId);
        } else {
            $recipientId = $request['recipientId'];
            $recipient = $survey->recipients()
                ->where('recipientId', '=', $recipientId)
                ->first();

            $answers = $survey->answers()
                ->where('answeredById', '=', $recipientId);
        }

        $answers = $answers->get()
            ->sort(function($a, $b) {
                if ($a->surveyCategory()->order === $b->surveyCategory()->order) {
                    if ($a->surveyQuestion()->order === $b->surveyQuestion()->order) {
                        return 0;
                    }

                    return $a->surveyQuestion()->order < $b->surveyQuestion()->order ? -1 : 1;
                }

                return $a->surveyCategory()->order < $b->surveyCategory()->order ? -1 : 1;
            });

        return view('survey.viewAnswers')
            ->with('survey', $survey)
            ->with('recipient', $recipient)
            ->with('answers', $answers);
    }

    /**
     * Returns the view for the given candidate
     */
    public function showCandidate(Request $request, $id)
    {
        $this->validate($request, [
            'candidateId' => 'required|integer'
        ]);

        $survey = \App\Models\Survey::findOrFail($id);

        if (!\App\SurveyTypes::isIndividualLike($survey->type)) {
            return redirect(action('SurveyController@index'));
        }

        $candidate = $survey->candidates()
            ->where('recipientId', '=', $request->candidateId)
            ->first();

        if ($candidate != null) {
            return view('survey.viewCandidate')
                ->with('survey', $survey)
                ->with('candidate', $candidate);
        } else {
            return redirect(action('SurveyController@index'));
        }
    }

    /**
     * Returns the view for the given role
     */
    public function showRole(Request $request, $id)
    {
        $this->validate($request, [
            'roleId' => 'required|integer'
        ]);

        $survey = \App\Models\Survey::findOrFail($id);

        if (!\App\SurveyTypes::isGroupLike($survey->type)) {
            return redirect(action('SurveyController@index'));
        }

        $roleGroup = $survey->roleGroups()
            ->where('roleId', '=', $request->roleId)
            ->first();

        if ($roleGroup != null) {
            return view('survey.viewRole')
                ->with('survey', $survey)
                ->with('roleGroup', $survey->roleWithMembers($roleGroup));
        } else {
            return redirect(action('SurveyController@index'));
        }
    }

    /**
    * View for creating a comparision survey
    */
    public function createComparisonView(Request $request, $id)
    {
        $survey = \App\Models\Survey::findOrFail($id);

        if ($survey->type != SurveyTypes::Progress) {
            return redirect(action('SurveyController@index'));
        }

        return view('survey.createComparison', compact('survey'));
    }

    /**
    * Creates a comparision survey
    */
    public function createComparison(Request $request, $id)
    {
        $survey = \App\Models\Survey::findOrFail($id);

        if ($survey->type != SurveyTypes::Progress) {
            return redirect(action('SurveyController@index'));
        }

        $this->validate($request, [
            'surveyName' => 'required',
            'startDate' => 'required|date_format:' . SurveyController::DATE_FORMAT,
            'endDate' => 'required|date_format:' . SurveyController::DATE_FORMAT . '|after_survey_date:startDate',
        ]);

        $getEmail = function ($requestName) use (&$request) {
            return (object)[
                'subject' => $request[$requestName . 'Subject'],
                'text' => $request[$requestName . 'Text'],
            ];
        };

        $newSurvey = Surveys::copy(app(), $survey, (object)[
            'name' => $request->surveyName,
            'startDate' => $request->startDate,
            'endDate' => $request->endDate,
            'toEvaluateText' => $getEmail('toEvaluateInvitation'),
            'userReportText' => $getEmail('userReport'),
            'invitationText' => $getEmail('invitation'),
            'manualRemindingText' => $getEmail('reminder'),
            'inviteOthersRemindingText' => $getEmail('inviteOthersReminder'),
            'description' => $request->description,
            'inviteText' => $request->inviteText,
            'questionInfoText' => $request->questionInfo,
            'thankYouText' => $request->thankYou,
        ]);

        return view('survey.created')->with('survey', $newSurvey);
    }

    /**
     * Executes the automatic reminders
     */
    public function executeAutoReminders(Request $request)
    {
        $timeNow = \Carbon\Carbon::now(Survey::TIMEZONE);

        $surveys = \App\Models\Survey::
            where('enableAutoReminding', '=', true)
            ->get();

        foreach ($surveys as $survey) {
            if ($timeNow->gte($survey->autoRemindingDate)) {
                foreach ($survey->recipients->where('hasAnswered', '=', false) as $surveyRecipient) {
                    $this->sendReminder($survey, $surveyRecipient);
                }

                $survey->enableAutoReminding = false;
                $survey->save();
            }
        }

        return '';
    }

    /**
    * Creates user report links for progress reports
    */
    public function executeCreateUserReportLink(Request $request)
    {
        $timeNow = \Carbon\Carbon::now(Survey::TIMEZONE);

        $progressSurveys = \App\Models\Survey::
            where('type', '=', \App\SurveyTypes::Progress)
            ->get();

        foreach ($progressSurveys as $survey) {
            foreach ($survey->candidates as $candidate) {
                $endDate = $candidate->endDateRecipients;
                // $survey->endDateFor($candidate->recipientId, $candidate->recipientId)

                if ($endDate == null) {
                    $endDate = $survey->endDate;
                }

                if ($timeNow->gt($endDate)) {
                    Surveys::createUserReportLink(app(), $survey, $candidate, false);
                }
            }
        }
    }

    /**
    * Indicates that an email bounced
    */
    public function emailBounced(Request $request)
    {
        $survey = \App\Models\Survey::where('id', '=', $request->surveyId)->first();

        if ($survey != null) {
            $recipient = $survey->recipients()
                ->where('recipientId', '=', $request->recipientId)
                ->first();

            if ($recipient != null) {
                $recipient->bounced = true;
                $recipient->save();
            }
        }
    }
}
