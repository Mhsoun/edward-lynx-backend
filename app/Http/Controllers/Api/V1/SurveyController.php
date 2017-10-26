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
use App\Exceptions\SurveyExpiredException;
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

            // Only return 1 page of results.
            if (is_null($request->page) || $request->page == 1) {
                $recipients = Recipient::where('mail', $user->email)
                                ->get()
                                ->map(function($recipient) {
                                    return $recipient->id;
                                })
                                ->toArray();

                $invites = SurveyRecipient::whereIn('recipientId', $recipients)
                                          ->get();
                $surveys = $invites->map(function($sr) {
                        $json = $sr->survey->jsonSerialize();
                        $json['description'] = $sr->generateDescription($sr->survey->description);
                        $json['personsEvaluatedText'] = $json['description'];
                        return $json;
                    })->sortBy(function($json) { // Sort by end date (deadline) and status.
                        return sprintf('%d-%s', $json['status'], $json['endDate']);
                    })->filter(function($json) use ($supportedTypes) {
                        return in_array($json['type'], $supportedTypes);
                    })->values();
            } else {
                $surveys = [];
            }

            return response()->jsonHal([
                                'total' => count($surveys),
                                'num'   => count($surveys),
                                'pages' => 1,
                                'items' => $surveys
                             ])
                             ->summarize();

        } else {
            // $candidates = Recipient::where('mail', $user->email)
                            // ->get()
                            // ->map(function($item) {
                                // return $item->id;
                            // })
                            // ->toArray();

            $surveys = Survey::select('surveys.*')
                           // ->join('survey_candidates', 'surveys.id', '=', 'survey_candidates.surveyId')
                           // ->whereIn('survey_candidates.recipientId', $candidates)
                           ->where('surveys.ownerId', $user->id)
                           // ->valid()
                           ->whereIn('type', $supportedTypes)
                           ->latest('surveys.endDate')
                           ->paginate($num);

            return response()->jsonHal($surveys)
                             ->summarize();
        }
    }
	
	/**
	 * Creates a survey.
	 *
     * @param   Illuminate\Http\Request         $request
	 * @return  Illuminate\Http\Response
	 */
	public function create(Request $request)
	{        
		$this->validate($request, [
			'name'	                            => 'required|max:255',
			'lang'	                            => 'required|in:en,fi,sv',
			'type'	                            => 'required|in:individual',
            'startDate'                         => 'required|isodate',
            'endDate'                           => 'required|isodate',
            'description'                       => 'string',
            'thankYouText'                      => 'string',
            'questionInfo'                      => 'string',
            'questions'                         => 'required|array',
            'questions.*.category.title'        => 'required|string',
            'questions.*.category.description'  => 'string',
            'questions.*.items'                 => 'required|array',
            'questions.*.items.*.text'          => 'required|string',
            'questions.*.items.*.isNA'          => 'required|boolean',
            'questions.*.items.*.answer.type'   => 'required|in:0,1,2,3,4,5,6,7,8',
            'questions.*.items.*.answer.options'=> 'array',
            'candidates'                        => 'required|array',
            'candidates.*.id'                   => 'required_without_all:candidates.*.name,candidates.*.email|integer|exists:users,id',
            'candidates.*.name'                 => 'required_without:candidates.*.id|string',
            'candidates.*.email'                => 'required_without:candidates.*.id|email'
		], [
		    'type.in'                           =>  'Only Lynx 360 (individual) types are accepted.'
		]);
            
        // Convert the string type to our internal representation
        // of a survey type.
        $types = [
            'individual'    => SurveyTypes::Individual
        ];
        $type = $types[$request->type];
    
        // Make sure that the current user can create this survey type.
        $this->authorize('create', [Survey::class, $type]);
        
        // Make sure the candidates are within the user's company only.
        $this->validateCandidates($request->user(), $request->candidates);
        
        // Create our draft survey.
        $surveyData = $this->generateSurveyData($type, $request);
        $survey = Surveys::create(app(), $surveyData);
        $url = route('api1-survey', ['survey' => $survey]);
        
        return response('', 201, ['Location' => $url]);
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
        return response()->jsonHal($survey);
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
        $recipients = Recipient::where('mail', $currentUser->email)
                        ->get()
                        ->map(function($recipient) {
                            return $recipient->id;
                        });
        $surveyRecipient = SurveyRecipient::where('link', $key)
                        ->whereIn('recipientId', $recipients)
                        ->firstOrFail();

        event(new SurveyKeyExchanged($currentUser, $key));

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
            throw new SurveyExpiredException;
        }

        $currentUser = $request->user();
        $recipients = Recipient::where('mail', $currentUser->email)
                        ->get()
                        ->map(function ($item) {
                            return $item->id;
                        })
                        ->toArray();

        if ($survey->type == 3) {
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
     * Cleans up strings.
     *
     * @param   string  $str
     * @return  string
     */
    protected function sanitize($str)
    {
        return htmlspecialchars($str);
    }
	
    /**
     * Creates the survey data structure required by Surveys::create().
     *
     * @param   integer                     $type
     * @param   Illuminate\Http\Request     $input
     * @return  object
     */
	protected function generateSurveyData($type, Request $request)
	{
        $user = $request->user();
        
		$data               = new stdClass();
        $data->name         = $this->sanitize($request->name);
        $data->type         = $type;
        $data->lang         = $request->lang;
        $data->ownerId      = $request->user()->id;
        $data->startDate    = dateFromIso8601String($request->startDate);
        $data->endDate      = $request->has('endDate') ? dateFromIso8601String($request->endDate) : null;
        
        $data->description  = $this->getTextOrDefault($request, 'description', 'defaultInformationText', $data->type);
        $data->thankYou     = $this->getTextOrDefault($request, 'thankYou', 'defaultThankYouText', $data->type);
        $data->questionInfo = $this->getTextOrDefault($request, 'questionInfo', 'defaultQuestionInfoText', $data->type);
        
        $data->individual   = $this->generate360Data($request, $data->type);
        $data->emails       = $this->generateEmails($request, $data->type);
        
        $this->processQuestions($data, $request->questions);
        $this->processCandidates($data, $request->candidates);
            
        return $data;
	}
    
    /**
     * Generates the data under the "individual" key for survey data.
     *
     * @param   Illuminate\Http\Request $request
     * @param   int                     $surveyType
     * @return  object
     */
    protected function generate360Data($request, $surveyType)
    {
        $user = $request->user();
        
        $individual = new stdClass();
        $individual->inviteText = $this->getTextOrDefault($request, 'inviteText', 'defaultInviteOthersInformationText', $surveyType);
        $individual->candidates = [];
        return $individual;
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
     * Converts submitted questions into data understood by
     * Surveys::create()
     *
     * @param   object  $data
     * @param   array   $questions
     * @return  void
     */
    protected function processQuestions($data, array $questions)
    {
        foreach ($questions as $index => $set) {
            $c = $set['category'];
            
            $category = new QuestionCategory;
            $category->title = $this->sanitize($c['title']);
            $category->lang = $data->lang;
            $category->description = empty($c['description']) ? '' : $this->sanitize($c['description']);
            $category->ownerId = $data->ownerId;
            $category->targetSurveyType = $data->type;
            $category->save();
            
            $data->categories[] = (object) [
                'id'    => $category->id,
                'order' => $index
            ];
            
            foreach ($set['items'] as $qIndex => $q) {
                $question = new Question;
                $question->text = $this->sanitize($q['text']);
                $question->ownerId = $data->ownerId;
                $question->categoryId = $category->id;
                $question->answerType = intval($q['answer']['type']);
                $question->isNA = $q['isNA'];
                $question->save();
                
                $data->questions[] = (object) [
                    'id'    => $question->id,
                    'order' => $qIndex
                ];
            }
        }
    }
    
    /**
     * Converts user IDs to valid objects that are accepted by
     * Surveys::create().
     *
     * @param   object  $data
     * @param   array   $candidates
     * @return  array
     */
    protected function processCandidates($data, array $candidates)
    {
        $results = [];
        foreach ($candidates as $candidate) {
            if (isset($candidate['id'])) {
                $user = User::find($candidate['id']);
            
                if (!$user) {
                    continue;
                }
                
                $results[] = (object) [
                    'userId'    => $user->id,
                    'name'      => $user->name,
                    'email'     => $user->email,
                    'position'  => ''
                ];
            } else {
                $results[] = (object) [
                    'name'      => $candidate['name'],
                    'email'     => $candidate['email'],
                    'position'  => ''
                ];
            }
        }
        $data->individual->candidates = $results;
    }
    
    /**
     * Validates candidate user IDs by making sure they exist and
     * the current user can access them.
     *
     * @param   App\Models\User $user
     * @param   array           $candidates
     * @return  void
     */
    protected function validateCandidates(User $user, array $candidates)
    {
        $errors = [];
        foreach ($candidates as $index => $c) {
            if (!isset($c['id'])) {
                continue;
            }
            
            $candidate = User::find($c['id']);
            if (!$candidate) {
                $errors["candidates.{$index}.id"][] = 'Candidate does not exist.';
            }
            
            if (!$candidate->can('view', $user)) {
                $errors["candidates.{$index}.id"][] = 'Invalid candidate.';
            }
        }
        
        if (!empty($errors)) {
            throw new CustomValidationException($errors);
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

}