<?php namespace App\Http\Controllers\Api\V1;

use App\Surveys;
use App\SurveyTypes;
use App\Models\Survey;
use App\Models\EmailText;
use App\Models\DefaultText;
use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;

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
	 * @return void
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
        
		$user = $request->user();
		
		// TODO: make sure that the user can create this type of survey!
		$surveyData = $this->processNewSurvey($request->all());
		$survey = Surveys::create(app(), $surveyData);
		$type = SurveyTypes::stringToCode($request->type);
		
		$survey = new Survey($request->only('name'));
		$survey->lang = $request->lang;
		$survey->type = $type;
		//$survey->description = DefaultText::getDefaultText($user, DefaultText::InviteEmail, $survey->type, $survey->lang);
		$survey->startDate = '0000-00-00 00:00:00';
		$survey->endDate = '0000-00-00 00:00:00';
		
		// Create default email texts
		$defaultEmails = [
			'invitationTextId'				=> DefaultText::InviteEmail,
			'manualRemindingTextId'			=> DefaultText::ReminderEmail,
			'toEvaluateInvitationTextId'	=> DefaultText::InviteOthersEmail,
			'candidateInvitationTextId'		=> DefaultText::InviteCandidateEmail,
			'inviteOthersReminderEmail'		=> DefaultText::InviteRemindingMail
		];
		foreach ($defaultEmails as $field => $type) {
			$defaultText = DefaultText::getDefaultText($user, $type, $survey->type, $survey->lang);
			if (!empty($default))
			$email = EmailText::make($user, $defaultText->subject, $defaultText->text, $survey->lang);
			$survey->{$field} = $email->id;
		}
		
		$survey->save();
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
	
	protected function processNewSurvey(array $input)
	{
		
	}

}