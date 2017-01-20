<?php namespace App\Http\Controllers\Api\V1;

use stdClass;
use App\Surveys;
use Carbon\Carbon;
use App\SurveyTypes;
use App\Models\User;
use App\Models\Survey;
use App\Models\Question;
use App\Models\EmailText;
use App\Models\Recipient;
use App\Models\DefaultText;
use Illuminate\Http\Request;
use App\Http\JsonHalCollection;
use Illuminate\Validation\Rule;
use App\Models\QuestionCategory;
use App\Http\Controllers\Controller;
use App\Exceptions\CustomValidationException;
use Illuminate\Auth\Access\AuthorizationException;

class SurveyController extends Controller
{

    /**
     * Returns a list of surveys the user can access.
     *
     * @param   Illuminate\Http\Request     $request
     * @return  JSONResponse
     */
    public function index(Request $request)
    {
        $this->validate($request, [
            'num'   => 'integer|between:1,50'
        ]);
        
        $num = intval($request->input('num', 10));
        $user = $request->user();

        // Fetch all surveys if we are a superadmin.
        if ($user->can('viewAll', Survey::class)) {
            $surveys = Survey::select('*');
        } else {
            $surveys = Survey::where('ownerId', $user->id);
        }

        $surveys = $surveys->valid()
                           ->where('type', SurveyTypes::Individual)
                           ->latest('startDate')
                           ->paginate($num);
        
        return response()->jsonHal($surveys)
                         ->summarize();
    }
	
	/**
	 * Creates a survey.
	 *
     * @param   Illuminate\Http\Request         $request
	 * @return  Illuminate\Http\JsonResponse
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
            'candidates.*.id'                   => 'required|integer'
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
     * @return  App\Http\HalResponse
     */
    public function show(Request $request, Survey $survey)
    {
        $json = $survey->jsonSerialize();
        return response()->jsonHal($json);
    }
    
    /**
     * Updates survey information.
     *
     * @param   Illuminate\Http\Request $request
     * @param   App\Models\Survey       $survey
     * @return  App\Http\HalResponse
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
                    $val = Carbon::parse($val);
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
     * @param   App\Models\Survey   $survey
     * @return  App\Http\HalResponse
     */
    public function questions(Survey $survey)
    {
        $url = route('api1-survey-questions', $survey);
        $categories = new JsonHalCollection($survey->categoriesAndQuestions(true), $url);
        
        return response()->jsonHal($categories);
    }
    
    /**
     * Answers a survey.
     *
     * @param   Illuminate\Http\Request $request
     * @param   App\Models\Survey       $survey
     * @return  Illuminate\Http\Response
     */
    public function answer(Request $request, Survey $survey)
    {
        $this->validate($request, [
            'key'                   => [
                'required',
                Rule::exists('survey_recipients', 'link')->where(function ($query) {
                    $query->where('hasAnswered', 0);
                })
            ],
            'answers'               => 'required|array'
            'final'                 => 'boolean',
        ]);
        
        // Input items
        $key = $request->key;
        $answers = [];
        foreach ($request->answers as $answer) {
            $answers[$answer['question']] = $answer['answer'];
        }
        $final = $request->input('final', true);
        $user = $request->user();
        
        // Make sure the current user owns the key.
        if ($key !== $survey->answerKeyOf($user)) {
            throw new CustomValidationException([
                'key'   => ['Invalid answer key.']
            ]);
        }
        
        // Make sure this survey hasn't expired yet.
        if ($survey->endDate->isPast()) {
            throw new SurveyExpiredException();
        }
        
        // Validate answers.
        $errors = $this->validateAnswers($questions, $answers);
        if (!empty($errors)) {
            throw new CustomValidationException($errors);
        }
        
        // Save our answers.
        foreach ($questions as $q) {
            $question = $q->question;
            $answer = empty($answers[$question->id]) ? null : $answers1[$question->id];
            
            // Skip if we don't have an answer.
            if ($answer === null) {
                continue;
            }
            
            // Create our answer.
            $surveyAnswer = new SurveyAnswer();
            $surveyAnswer->surveyId = $recipient->survey->id;
            $surveyAnswer->answeredById = $recipient->recipient->id;
            $surveyAnswer->questionId = $question->id;
            $surveyAnswer->invitedById = $recipient->invitedById;
            if ($question->answerTypeObject()->isNumeric()) {
                $surveyAnswer->answerValue = $answer;
            } else {
                $surveyAnswer->answerText = $answer;
            }
            
            $surveyAnswer->save();
        }
        
        // Mark the invite as answered.
        // TODO: reenable
        if ($final) {
            // $recipient->hasAnswered = 1;
            // $recipient->save();
        }
        
        return response()->jsonHal($this->recipientAnswers($recipient));
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
        $data->startDate    = Carbon::parse($request->startDate);
        $data->endDate      = $request->has('endDate') ? Carbon::parse($request->endDate) : null;
        
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
        }
        $data->individual->candidates = $results;
    }
    
    /**
     * Ensures that the submitted answers are valid for each question.
     *
     * @param   Illuminate\Database\Eloquent\Collection $questions
     * @param   array                                   $answers
     * @return  array
     */
    protected function validateAnswers(Collection $questions, array $answers)
    {
        $errors = [];
        
        foreach ($questions as $q) {
            $question = $q->question;
            $answer = $question->answerTypeObject();
            $key = "questions.{$question->id}";
            
            // Validate answers
            if (empty($answers[$question->id])) {
                // Make sure non-optional questions have an answer.
                if (!$question->optional) {
                    $errors[$key][] = "Missing answer for question with ID {$question->id}.";
                }
            } else {
                $ans = $answers[$question->id];
                
                // Questions that does not accept N/A should not receive one.
                if ($ans === -1 && !$question->isNA) {
                    $errors[$key][] = "N/A answer is not accepted.";
                // Ensure that the answer is a valid one.
                } elseif (!$answer->isValidValue($ans)) {
                    $errors[$key][] = "'{$ans}' is not a valid answer.";
                }
            }
        }
        
        return $errors;
    }
    
    protected function recipientAnswers(SurveyRecipient $recipient)
    {
        $survey = $recipient->survey;
        $result = [
            'key'       => $recipient->link,
            'final'     => $recipient->hasAnswered,
            'answers'   => []
        ];
        
        foreach ($recipient->answers as $answer) {
            
        }
    }

}