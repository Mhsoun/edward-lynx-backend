<?php namespace App\Http\Controllers\Api\V1;

use stdClass;
use App\Surveys;
use Carbon\Carbon;
use App\SurveyTypes;
use App\Models\Survey;
use App\Models\EmailText;
use App\Models\DefaultText;
use Illuminate\Http\Request;
use App\Http\JsonHalCollection;
use App\Http\Controllers\Controller;
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

        $surveys = $surveys->latest('startDate')
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
			'type'	                            => 'required|in:instant',
            'startDate'                         => 'required|isodate',
            'endDate'                           => 'required|isodate',
            'description'                       => 'string',
            'thankYouText'                      => 'string',
            'questionInfo'                      => 'string',
            'recipients'                        => 'required|array',
            'recipients.*.name'                 => 'required|string',
            'recipients.*.email'                => 'required|email',
            'recipients.*.position'             => 'required|string'
		], [
		    'type.in'                           =>  'Only Instant Feedback (instant) types are accepted.'
		]);
            
        if ($request->type === SurveyTypes::Instant) {
            $this->validate($request, [
                'questions'                         => 'required|array|size:1',
                'questions.*.text'                  => 'required|string',
                'questions.*.isNA'                  => 'required|boolean',
                'questions.*.answer.type'           => 'required|in:0,1,2,3,4,5,6,7,8',
                'questions.*.answer.options'        => 'array',
                'questions.*.answer.*.description'  => 'string',
                'questions.*.answer.*.value'        => 'string'
            ]);
        }
            
        // Convert the string type to our internal representation
        // of a survey type.
        $types = [
            'instant'    =>  SurveyTypes::Instant,
        ];
        $type = $types[$request->type];
        
        // Make sure that the current user can create this survey type.
        $this->authorize('create', [Survey::class, $type]);
        
        // Create our draft survey.
        $surveyData = $this->generateSurveyData($request);
        $survey = Surveys::create(app(), $surveyData);
        $url = route('api1-survey', ['survey' => $survey]);
        
        return response()->json($survey, 201)
                         ->header('Location', $url);
	}

    /**
     * Returns survey information.
     * 
     * @param  Survey       $survey
     * @return JSONResponse 
     */
    public function show(Survey $survey)
    {
        return response()->jsonHal($survey);
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
     * @param   Illuminate\Http\Request     $input
     * @return  object
     */
	protected function generateSurveyData(Request $request)
	{
        $user = $request->user();
        
		$data               = new stdClass();
        $data->name         = $this->sanitize($request->name);
        $data->type         = intval($request->type);
        $data->lang         = $request->lang;
        $data->ownerId      = $request->user()->id;
        $data->startDate    = Carbon::parse($request->startDate);
        $data->endDate      = Carbon::parse($request->endDate);
        
        $data->description  = $this->getTextOrDefault($request, 'description', 'defaultInformationText', $data->type);
        $data->thankYou     = $this->getTextOrDefault($request, 'thankYou', 'defaultThankYouText', $data->type);
        $data->questionInfo = $this->getTextOrDefault($request, 'questionInfo', 'defaultQuestionInfoText', $data->type);
        
        $data->individual   = $this->generate360Data($request, $data->type);
        $data->emails       = $this->generateEmails($request, $data->type);
        
        $data->categories = [];
        if ($data->type == SurveyTypes::Instant) {
            $data->categories = $this->createDefaultCategory();
        }
        
        $data->questions = [];
        if ($data->type == SurveyTypes::Instant) {
            $data->questions = $this->processQuestions($request->questions);
        }
            
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
    
    protected function createDefaultCategory()
    {
        // Create new default category here.
    }

    protected function processQuestions($value='')
    {
        # code...
    }

}