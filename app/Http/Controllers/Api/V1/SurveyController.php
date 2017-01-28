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
use App\Models\SurveyAnswer;
use Illuminate\Http\Request;
use App\Http\JsonHalCollection;
use Illuminate\Validation\Rule;
use App\Models\QuestionCategory;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
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
                           ->latest('endDate')
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
     * @param   Illuminate\Http\Request $request
     * @param   App\Models\Survey       $survey
     * @return  App\Http\HalResponse
     */
    public function questions(Request $request, Survey $survey)
    {
        $url = route('api1-survey-questions', $survey);
        
        // Fetch the user's answers
        $questionToAnswers = [];
        $answers = $survey->answers()->where([
            'answeredById'      => $request->user()->id,
            'answeredByType'    => 'users'
        ])->getResults();
        foreach ($answers as $answer) {
            $questionToAnswers[$answer->questionId] = $answer->answerValue ? $answer->answerValue : $answer->answerText;
        }
         
        // Include the user's answer to each question 
        $categories = new JsonHalCollection($survey->categoriesAndQuestions(true), $url);
        $json = $categories->map(function($item) use ($questionToAnswers) {
            $json = $item->jsonSerialize();
            $json['questions'] = array_map(function($question) use ($questionToAnswers) {
                $questionId = $question['id'];
                $question['value'] = isset($questionToAnswers[$questionId]) ? $questionToAnswers[$questionId] : null;
                return $question;
            }, $json['questions']);
            return $json;
        });
        
        return response()->jsonHal($json);
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

}