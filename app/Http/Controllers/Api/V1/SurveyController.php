<?php namespace App\Http\Controllers\Api\V1;

use stdClass;
use App\Surveys;
use Carbon\Carbon;
use App\SurveyTypes;
use App\Models\Survey;
use App\Models\EmailText;
use App\Models\DefaultText;
use Illuminate\Http\Request;
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
        
        // Append num parameter if it is present.
        $nextPage = $surveys->nextPageUrl();
        $prevPage = $surveys->previousPageUrl();
        if ($request->has('num')) {
            $nextPage = $nextPage ? "{$nextPage}&num={$num}" : null;
            $prevPage = $prevPage ? "{$prevPage}&num={$num}" : null; 
        }
        
        return response()->json([
            'total'         => $surveys->total(),
            'perPage'       => $surveys->perPage(),
            'totalPages'    => ceil($surveys->total() / $surveys->perPage()),
            'nextPageUrl'   => $nextPage,
            'prevPageUrl'   => $prevPage,
            'items'         => $surveys->items()
        ]);
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
			'name'	    => 'required|max:255',
			'lang'	    => 'required|in:en,fi,sv',
			'type'	    => 'required|in:individual',
		], [
		    'type.in'   =>  'Only Lynx 360 (individual) types are accepted.'
		]);
            
        // Convert the string type to our internal representation
        // of a survey type.
        $types = [
            'individual'    =>  SurveyTypes::Individual,
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
     * @param  Survey $survey
     * @return JSONResponse 
     */
    public function show(Survey $survey)
    {
        return response()->json($survey);
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
        $data->startDate    = Carbon::now();
        $data->endDate      = Carbon::now();
        
        $data->description  = $this->getTextOrDefault($request, 'description', 'defaultInformationText');
        $data->thankYou     = $this->getTextOrDefault($request, 'thankYou', 'defaultThankYouText');
        $data->questionInfo = $this->getTextOrDefault($request, 'questionInfo', 'defaultQuestionInfoText');
        
        $data->individual   = $this->generate360Data($request);
        $data->emails       = $this->generateEmails($request);
        
        $data->categories   = [];
        $data->questions    = [];
            
        return $data;
	}
    
    /**
     * Generates the data under the "individual" key for survey data.
     *
     * @param   Illuminate\Http\Request $request
     * @return  object
     */
    protected function generate360Data($request)
    {
        $user = $request->user();
        
        $individual = new stdClass();
        $individual->inviteText = $this->getTextOrDefault($request, 'inviteText', 'defaultInviteOthersInformationText');
        $individual->candidates = [];
        return $individual;
    }
    
    /**
     * Generates default emails under the "emails" key for survey data.
     *
     * @param   Illuminate\Http\Request $request
     * @return  object
     */
    protected function generateEmails($request)
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
            $default = $user->{$method}(SurveyTypes::Individual, $lang);
            $emails->{$key}->subject = $default->subject;
            $email->{$key}->text = $default->message;
            $email->{$key}->lang = $lang;
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
     * @return  string
     */
    protected function getTextOrDefault(Request $request, $field, $method)
    {
        if ($request->has($field)) {
            return $request->input($field);
        } else {
            return $request->user()->{$method}(SurveyTypes::Individual, $request->lang)->text;
        }
    }

}