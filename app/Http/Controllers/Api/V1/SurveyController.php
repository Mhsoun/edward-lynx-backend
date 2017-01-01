<?php namespace App\Http\Controllers\Api\V1;

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
            'per_page'  => 'integer|between:1,50'
        ]);
            
        $perPage = intval($request->input('per_page', 10));
        $user = $request->user();

        if ($user->can('viewAll', Survey::class)) {
            $surveys = Survey::select('*');
        } else {
            $surveys = Survey::where('ownerId', $user->id);
        }

        $surveys = $surveys->latest('startDate')
                           ->paginate($perPage);

        return response()->json([
            'total'         => $surveys->total(),
            'perPage'       => $surveys->perPage(),
            'totalPages'    => ceil($surveys->total() / $surveys->perPage()),
            'nextPageUrl'   => $surveys->nextPageUrl(),
            'prevPageUrl'   => $surveys->previousPageUrl(),
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
		$user = $request->user();
		
		$this->validate($request, [
			'name'	=> 'required|max:255',
			'lang'	=> 'required|in:en,fi,sv',
			'type'	=> 'required|in:individual,group,progress,ltt,normal',
		]);
		
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