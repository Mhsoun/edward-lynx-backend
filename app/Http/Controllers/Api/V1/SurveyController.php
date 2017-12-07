<?php namespace App\Http\Controllers\Api\V1;

use stdClass;
use App\Roles;
use App\Surveys;
use Carbon\Carbon;
use App\SurveyTypes;
use App\Models\User;
use App\SurveyEmailer;
use App\Models\Survey;
use App\Models\Question;
use App\Models\EmailText;
use App\Models\Recipient;
use App\EmailContentParser;
use App\Models\DefaultText;
use App\Models\SurveyAnswer;
use Illuminate\Http\Request;
use UnexpectedValueException;
use App\Models\SurveyRecipient;
use App\Models\SurveyCandidate;
use App\Http\JsonHalCollection;
use Illuminate\Validation\Rule;
use App\Models\QuestionCategory;
use App\Events\SurveyKeyExchanged;
use App\Http\Controllers\Controller;
use App\Notifications\SurveyInvitation;
use App\Notifications\SurveyAnswerRequest;
use App\Notifications\SurveyInviteRequest;
use Illuminate\Database\Eloquent\Collection;
use App\Exceptions\CustomValidationException;
use App\Exceptions\InvalidOperationException;
use Illuminate\Auth\Access\AuthorizationException;

class SurveyController extends Controller
{

    protected $emailer;

    /**
     * Creates a new controller instance.
     * 
     * @param   App\SurveyEmailer   $emailer
     */
    public function __construct(SurveyEmailer $emailer)
    {
        $this->emailer = $emailer;
    }

    /**
     * Returns a list of surveys the user can access.
     *
     * @param   Illuminate\Http\Request     $request
     * @return  App\Http\JsonHalResponse
     */
    public function index(Request $request)
    {
        $this->validate($request, [
            'num'       => 'integer|between:1,50',
            'filter'    => 'string'
        ]);
        
        $num = intval($request->input('num', 10));
        $user = $request->user();
        $statusSorter = function($a, $b) use ($user) {
            if ($a->status($user) < $b->status($user)) {
                return -1;
            } elseif ($a->status($user) > $b->status($user)) {
                return 1;
            } else {
                return 0;
            }
        };

        $now = Carbon::now();
        $supportedTypes = [SurveyTypes::Individual, SurveyTypes::Progress, SurveyTypes::Normal];
        if ($request->filter === 'answerable') {

            $surveys = collect();
            $recipientIds = Recipient::recipientIdsOfUser($user);
            
            // Fetch all surveys where the current user is a recipient of.
            SurveyRecipient::whereIn('recipientId', $recipientIds)
                ->get()
                ->map(function($sr) use ($surveys) {
                    $candidate = $sr->survey->type == SurveyTypes::Individual ? $sr->invitedByCandidate() : $sr;
                    $json = $this->serializeSurvey($sr->survey, $sr);
                    $json['status'] = $sr->answerStatus($candidate);
                    $surveys->push($json);
                });

            // Sort by status and end date.
            $surveys->sortBy(function($json) {
                return sprintf('%d-%s', $json['status'], $json['endDate']);
            // Then remove "team" surveys.
            })->filter(function($json) use ($supportedTypes) {
                return in_array($json['type'], $supportedTypes);
            })->values();

            // Return an empty array on pages other than 1
            if ($request->page >= 2) {
                return response()->jsonHal([
                    'total' => count($surveys),
                    'num'   => count($surveys),
                    'pages' => 1,
                    'items' => [],
                ]);
            }

            return response()->jsonHal([
                                'total' => count($surveys),
                                'num'   => count($surveys),
                                'pages' => 1,
                                'items' => $surveys
                             ])
                             ->summarize();

        } else {
            $surveys = Survey::select('surveys.*')
                           ->where('surveys.ownerId', $user->id)
                           ->whereIn('type', $supportedTypes)
                           ->latest('surveys.endDate')
                           ->paginate($num);

            return response()->jsonHal($surveys)
                             ->summarize();
        }
    }

    /**
     * Returns survey information.
     * 
     * @param   Illuminate\Http\Request $request
     * @param   App\Models\Survey       $survey
     * @return  App\Http\JsonHalResponse
     */
    public function show(Request $request, Survey $survey)
    {
        $currentUser = $request->user();
        $key = $request->key;

        // Immediately return the survey details if this is the owner.
        if ($survey->ownerId == $currentUser->id) {
            return response()->jsonHal($survey);
        }

        // For everyone else, validate the key.
        if (!SurveyCandidate::userIsValidCandidate($survey, $currentUser, $key) &&
            !SurveyRecipient::userIsValidRecipient($survey, $currentUser, $key)) {
            throw new AuthorizationException('Invalid access key.');
        }

        $json = $survey->jsonSerialize();

        // Generate the description
        $surveyRecipient = SurveyRecipient::findForUser($survey, $currentUser, $key);
        $json = $this->serializeSurvey($survey, $surveyRecipient);

        // Add disallowed recipients
        $disallowed = $this->listDisallowedRecipients($survey, [$currentUser->email]);
        $json['disallowed_recipients'] = $disallowed;

        // Mark the associated notification as read
        $notifications = $currentUser->unreadNotifications;
        foreach ($notifications as $notification) {
            if (isset($notification->data['surveyKey']) && $notification->data['surveyKey'] == $key) {
                $notification->markAsRead();
            }
        }

        return response()->jsonHal($json)
            ->withLinks($survey->jsonHalLinks());
    }
    
    /**
     * Updates survey information.
     *
     * @param   Illuminate\Http\Request $request
     * @param   App\Models\Survey       $survey
     * @return  App\Http\JsonHalResponse
     */
    public function update(Request $request, Survey $survey)
    {
        $rules = [
            'enableAutoReminding'   => 'boolean',
            'autoRemindingDate'     => 'isodate|after:today'
        ];
        $dates = ['autoRemindingDate'];
        $this->validate($request, $rules);
        
        foreach (array_keys($rules) as $field) {
            if ($request->has($field)) {
                $val = $request->{$field};
                
                if (in_array($field, $dates)) {
                    $val = dateFromIso8601String($val);
                }
                
                $survey->{$field} = $val;
            }
        }
        $survey->save();
        
        return response()->jsonHal($survey);
    }
    
    /**
     * Returns the survey's questions.
     *
     * @param   Illuminate\Http\Request $request
     * @param   App\Models\Survey       $survey
     * @return  App\Http\HalResponse
     */
    public function questions(Request $request, Survey $survey)
    {
        $url = route('api1-survey-questions', $survey);
        $currentUser = $request->user();
        $key = $request->key;

        // If there is no provided key, return the questions only.
        if ($key === null) {
            $categoriesAndQuestions = $survey->categoriesAndQuestions(true);
            return $categoriesAndQuestions;
        }

        // Validate the key.
        if (!SurveyCandidate::userIsValidCandidate($survey, $currentUser, $key) &&
            !SurveyRecipient::userIsValidRecipient($survey, $currentUser, $key)) {
            throw new AuthorizationException('Invalid access key.');
        }

        if (!$invite = SurveyCandidate::findForUser($survey, $currentUser, $key)) {
            $invite = SurveyRecipient::findForUser($survey, $currentUser, $key);
        }

        $questionToAnswers = [];
        $answers = $survey->answers()
                        //   ->where('recipientId', $invite->recipient->id)
                          ->where('answeredById', $invite->recipient->id)
                          ->where('invitedById', $invite instanceof SurveyCandidate ? $invite->recipientId : $invite->invitedById)
                          ->get();
        foreach ($answers as $answer) {
            $ans = $answer->jsonSerialize();
            $questionToAnswers[$answer->questionId] = [
                'value' => $ans['answer'],
            ];
        }

        $data = [
            'surveyName'    => $survey->name,
            'surveyLink'    => '',
            'surveyEndDate' => $survey->endDate->format('Y-m-d H:i'),
            'companyName'   => $survey->owner->parentId == null ? $survey->owner->name : $survey->owner->company->name,
        ];

        $categories = new JsonHalCollection($survey->categoriesAndQuestions(true), $url);
        $json = $categories->map(function($item) use ($questionToAnswers, $survey, $invite, $data) {
            $json = $item->jsonSerialize();
            $json['questions'] = array_map(function($question) use ($questionToAnswers, $survey, $invite, $data) {
                $questionId = $question['id'];
                $question['value'] = isset($questionToAnswers[$questionId]) ? $questionToAnswers[$questionId]['value'] : null;

                if (isset($questionToAnswers[$questionId]['explanation'])) {
                    $question['explanation'] = $questionToAnswers[$questionId]['explanation'];
                }

                $data = array_merge($data, [
                    'recipientName'     => $invite->recipient->name,
                    'toEvaluateName'    => $invite->invitedById != null ? $invite->invitedByObj->name : $invite->recipient->name,
                ]);
                // EmailContentParser::createParserData($survey, $invite);
                $question['text'] = EmailContentParser::parse($question['text'], $data);
                $question['text'] = strip_tags($question['text']);

                return $question;
            }, $json['questions']);

            return $json;
        });

        return response()->jsonHal($json);

        // --------

        $recipient = Recipient::findForOwner($survey->ownerId, $currentUser->email);

        // Find the invite record
        $surveyRecipient = SurveyRecipient::where([
            'recipientId'   => $recipient->id,
            'surveyId'      => $survey->id,
        ])->first();

        // Fetch the user's answers
        $questionToAnswers = [];
        $answers = $survey->answers()
                          ->where('answeredById', $recipient->id)
                          ->getResults();

        foreach ($answers as $answer) {
            $ans = $answer->jsonSerialize();
            $questionToAnswers[$answer->questionId] = [
                'value' => $ans['answer'] 
            ];

            if (isset($ans['explanation'])) {
                $questionToAnswers[$answer->questionId]['explanation'] = $ans['explanation'];
            }
        }
         
        // Include the user's answer to each question 
        $categories = new JsonHalCollection($survey->categoriesAndQuestions(true), $url);
        $json = $categories->map(function($item) use ($questionToAnswers, $survey, $surveyRecipient) {
            $json = $item->jsonSerialize();
            $json['questions'] = array_map(function($question) use ($questionToAnswers, $survey, $surveyRecipient) {
                $questionId = $question['id'];
                $question['value'] = isset($questionToAnswers[$questionId]) ? $questionToAnswers[$questionId]['value'] : null;
                
                if (isset($questionToAnswers[$questionId]['explanation'])) {
                    $question['explanation'] = $questionToAnswers[$questionId]['explanation'];
                }

                $data = EmailContentParser::createParserData($survey, $surveyRecipient);
                $question['text'] = EmailContentParser::parse($question['text'], $data);
                $question['text'] = strip_tags($question['text']);

                return $question;
            }, $json['questions']);
            return $json;
        });
        
        return response()->jsonHal($json);
    }

    /**
     * Exchange survey answer keys for a survey ID.
     * 
     * @param  Illuminate\Http\Request  $request
     * @param  string                   $key
     * @return App\Http\JsonHalResponse
     */
    public function exchange(Request $request, $key)
    {
        $currentUser = $request->user();
        $action = $request->input('action', 'answer');
        $recipients = Recipient::where('mail', $currentUser->email)
                        ->get()
                        ->map(function($recipient) {
                            return $recipient->id;
                        });
        $surveyRecipient = SurveyRecipient::where('link', $key)
                        ->whereIn('recipientId', $recipients)
                        ->firstOrFail();

        event(new SurveyKeyExchanged($currentUser, $key, $action));

        return response()->jsonHal([
            'survey_id' => $surveyRecipient->surveyId
        ]);
    }

    /**
     * Invites recipients to rate a candidate.
     * 
     * @param  App\Http\Request     $request
     * @param  App\Models\Survey    $survey
     * @return App\Http\JsonHalResponse
     */
    public function recipients(Request $request, Survey $survey)
    {
        $this->validate($request, [
            'recipients'                        => 'required|array',
            'recipients.*.id'                   => 'required_without_all:recipients.*.name,recipients.*.email|integer|exists:users,id',
            'recipients.*.name'                 => 'required_without:recipients.*.id|string',
            'recipients.*.email'                => 'required_without:recipients.*.id|email',
            'recipients.*.role'                 => 'required|in:2,3,4,5,6,7'
        ]);

        if ($survey->isClosed()) {
            throw new InvalidOperationException('Survey closed.');
        }

        $currentUser = $request->user();
        $recipients = Recipient::where('mail', $currentUser->email)
                        ->get()
                        ->map(function ($item) {
                            return $item->id;
                        })
                        ->toArray();

        if ($survey->type == SurveyTypes::Normal) { // Lynx Survey
            $inviter = SurveyRecipient::where('surveyId', $survey->id)
                    ->whereIn('recipientId', $recipients)
                    ->first();
        } else {
            $inviter = SurveyCandidate::where('surveyId', $survey->id)
                    ->whereIn('recipientId', $recipients)
                    ->first();
        }

        if ($inviter->recipientId == 0) {
            throw new UnexpectedValueException("Invalid recipient ID 0 for candidate.");
        }

        foreach ($request->recipients as $recipient) {
            if (!empty($recipient['id'])) {
                $this->inviteUserRecipient($survey, $inviter, $recipient);
            } else {
                $this->inviteAnonymousRecipient($survey, $inviter, $recipient);
            }
        }

        return createdResponse();
    }
    
    /**
     * Generates default emails under the "emails" key for survey data.
     *
     * @param   Illuminate\Http\Request $request
     * @param   int                     $surveyType
     * @return  object
     */
    protected function generateEmails($request, $surveyType)
    {
        $user = $request->user();
        $lang = $request->lang;
        $emails = new stdClass();
        
        $types = [
            'invitation'            => 'defaultInvitationEmail',
            'reminder'              => 'defaultReminderEmail',
            'toEvaluate'            => 'defaultToEvaluateEmail',
            'inviteOthersReminder'  => 'defaultCandidateInvitationEmail',
            'candidateInvite'       => 'defaultInviteRemindingEmail',
            // 'userReport'            => '',
            // 'toEvaluateRole'        => ''
        ];
        foreach ($types as $key => $method) {
            $default = $user->{$method}($surveyType, $lang);
            $emails->{$key} = new stdClass();
            $emails->{$key}->subject = $default->subject;
            $emails->{$key}->text = $default->message;
            $emails->{$key}->lang = $lang;
        }
        
        return $emails;
    }
    
    /**
     * Retrieves form field text if it is present, otherwise
     * it fetches the default text for that field.
     *
     * @param   Illuminate\Http\Request $request
     * @param   string                  $field
     * @param   string                  $method
     * @param   int                     $surveyType
     * @return  string
     */
    protected function getTextOrDefault(Request $request, $field, $method, $surveyType)
    {
        if ($request->has($field)) {
            return $request->input($field);
        } else {
            return $request->user()->{$method}($surveyType, $request->lang)->text;
        }
    }

    /**
     * Invites a guest recipient to rate a candidate.
     * 
     * @param  App\Models\Survey            $survey
     * @param  App\Models\SurveyCandidate
     *         App\Models\SurveyRecipient   $inviter
     * @param  array                        $recipient
     * @return App\Models\SurveyRecipient
     */
    public function inviteAnonymousRecipient(Survey $survey, $inviter, $recipient)
    {
        $owner = $survey->ownerId;
        $name = strip_tags($recipient['name']);
        $email = $recipient['email'];
        $role = Roles::valid($recipient['role']) ? $recipient['role'] : 1;

        $recipient = Recipient::make($owner, $name, $email, '');
        $existingRecipient = $survey->recipients()
            ->where('recipientId', '=', $recipient->id)
            ->where('surveyId', '=', $survey->id)
            ->where('invitedById', '=', $inviter->recipientId)
            ->first();

        if ($existingRecipient) {
            return $existingRecipient;
        }

        $endDatePassed = $survey->endDatePassed($inviter->recipientId, $inviter->recipientId);
        if ($endDatePassed) {
            throw new InvalidOperationException('Candidate has reached the end date!');
        }
        
        $surveyRecipient = $survey->addRecipient($recipient->id, $role, $inviter->recipientId);
        $this->emailer->sendSurveyInvitation($survey, $surveyRecipient);

        // Notify user with the same email as the recipient
        if ($user = User::where('email', $email)->first()) {
            $user->notify(new SurveyAnswerRequest($survey, $surveyRecipient->link));
            $user->notify(new SurveyInviteRequest($survey, $surveyRecipient->link));
        }

        return $surveyRecipient;
    }

    /**
     * Invites an existing user recipient to rate a candidate.
     * 
     * @param  App\Models\Survey            $survey
     * @param  App\Models\SurveyCandidate   $inviter
     * @param  array                        $recipient
     * @return App\Models\SurveyRecipient
     */
    public function inviteUserRecipient(Survey $survey, SurveyCandidate $inviter, $recipient)
    {
        $user = User::find($recipient['id']);
        $role = Roles::valid($recipient['role']) ? $recipient['role'] : 1;
        $owner = $survey->ownerId;

        $existingRecipient = $survey->recipients()
            ->where('recipientId', '=', $user->id)
            ->where('surveyId', '=', $survey->id)
            // ->where('invitedById', '=', $owner)
            ->first();

        if ($existingRecipient) {
            return $existingRecipient;
        }

        $endDatePassed = false;
        // $endDatePassed = $survey->endDatePassed($inviter->recipientId, $inviter->recipientId);
        // TODO: check if the candidate has reached it's end date.
        
        if ($endDatePassed) {
            throw new Exception('Candidate has reached the end date!');
        }

        $surveyRecipient = $survey->addRecipient($user->id, $role, $inviter->recipientId);
        $this->emailer->sendSurveyInvitation($survey, $surveyRecipient);

        $user->notify(new SurveyInvitation($survey->id, $surveyRecipient->link));

        return $surveyRecipient;
    }

    /**
     * Serializes a survey to JSON and generates the proper description
     * and personsEvaluatedText strings.
     *
     * @param App\Models\Survey $survey
     * @param App\Models\SurveyRecipient $recipient
     * @return array
     */
    protected function serializeSurvey(Survey $survey, SurveyRecipient $surveyRecipient)
    {
        if ($surveyRecipient->invitedByCandidate()) {
            $invitedBy = $surveyRecipient->invitedByCandidate()->recipient->name;
        } else {
            $invitedBy = $surveyRecipient->invitedByObj->name;
        }

        $json = $survey->jsonSerialize();
        $json['description'] = strip_tags($survey->generateDescription($surveyRecipient->recipient, $surveyRecipient->link));
        $json['personsEvaluatedText'] = sprintf('The person being evaluated is %s.', $invitedBy);
        $json['key'] = $surveyRecipient->link;

        return $json;
    }

    /**
     * Returns an array of emails not allowed to be invited in the given survey.
     *
     * @param App\Models\Survey $survey
     * @param array $additional
     * @return array
     */
    protected function listDisallowedRecipients(Survey $survey, $additional = [])
    {
        $disallowed = array_merge([], $additional);

        foreach ($survey->candidates as $surveyCandidate) {
            $disallowed[] = $surveyCandidate->recipient->mail;
        }

        foreach ($survey->recipients as $surveyRecipient) {
            $disallowed[] = $surveyRecipient->recipient->mail;
        }

        $disallowed = array_values(array_unique($disallowed));

        return $disallowed;
    }

}
