<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\SurveyEmailer;
use Lang;
use Auth;
use App\Models\User;
use App\Notifications\SurveyAnswerRequest;
use App\Notifications\SurveyInviteRequest;

/**
* Represents an invitation controller.
*/
class InviteController extends Controller
{
    protected $surveyEmailer;

    /**
     * Create a new controller instance.
     */
    public function __construct(SurveyEmailer $surveyEmailer)
    {
        $this->surveyEmailer = $surveyEmailer;
    }

    /**
    * Sets the locale based on the given survey
    */
    private function setSurveyLocale($survey)
    {
        app()->setLocale($survey->lang);
    }

    /**
    * Indicates if the current user is an admin for the given survey
    */
    private function isAdmin($surveyOwner)
    {
        return
            Auth::user() != null
            && (Auth::user()->isAdmin || $surveyOwner->id == Auth::user()->id);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $link)
    {
        $surveyCandidate = \App\Models\SurveyCandidate::where('link', '=', $link)->first();

        //Check that the candidate exists
        if ($surveyCandidate == null) {
            return view('answer.notfound');
        }

        $survey = $surveyCandidate->survey;
        $surveyOwner = $survey->owner;
        $surveyRecipient = $surveyCandidate->surveyRecipient();

        $invitedRecipients = $survey->recipients()
            ->where('invitedById', '=', $surveyCandidate->recipientId)
            ->where('recipientId', '!=', $surveyCandidate->recipientId)
            ->get();

        $isAdmin = false;

        if ($this->isAdmin($surveyOwner) && $request['admin'] == 'yes') {
            $isAdmin = true;
        } else {
            $this->setSurveyLocale($survey);
        }

        //If progress and not answered, display the answer page.
        if (\App\SurveyTypes::isNewProgress($survey) && !$surveyRecipient->hasAnswered) {
            return redirect(action('AnswerController@show', ['link' => $surveyRecipient->link]));
        }

        $parserData = \App\EmailContentParser::createParserData($survey, $surveyRecipient);
        return view(
            'invite.select',
            compact('survey', 'link', 'invitedRecipients', 'surveyOwner', 'surveyCandidate', 'isAdmin', 'parserData'));
    }

    /**
    * Adds a new recipient to the given survey (AJAX call).
    */
    public function addRecipient(Request $request, $link)
    {
        $inviter = \App\Models\SurveyCandidate::where('link', '=', $link)->first();
        $survey = $inviter->survey;
        $owner = $survey->ownerId;

        //Check if exists
        if ($inviter == null) {
            return response()->json([
                'success' => false
            ]);
        }

        $survey = $inviter->survey;

        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email',
            'roleId' => 'required|integer'
        ]);

        $name = $request->name;
        $email = $request->email;
        $roleId = $request->roleId;

        $recipient = \App\Models\Recipient::make($owner, $name, $email, '');

        $existingRecipient = $survey->recipients()
            ->where('recipientId', '=', $recipient->id)
            ->where('surveyId', '=', $survey->id)
            ->where('invitedById', '=', $inviter->recipientId)
            ->first();

        if ($existingRecipient == null) {
            $endDatePassed = $survey->endDatePassed($inviter->recipientId, $inviter->recipientId);

            if ($this->isAdmin($survey->owner)) {
                $endDatePassed = false;
            } else if ($survey->compareAgainstSurvey != null) {
                $endDatePassed = true;
            }

            if (!$endDatePassed) {
                $surveyRecipient = $survey->addRecipient(
                    $recipient->id,
                    \App\Roles::valid($roleId) ? $roleId : 1,
                    $inviter->recipientId);

                $this->surveyEmailer->sendSurveyInvitation($survey, $surveyRecipient);

                // Notify the user with the same email as the recipient
                if ($user = User::where('email', $email)->first()) {
                    $user->notify(new SurveyAnswerRequest($survey, $surveyRecipient->link));
                }

                return response()->json([
                    'id' => $recipient->id,
                    'success' => true
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => Lang::get('surveys.endDatePassedContent')
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => Lang::get('surveys.alreadyInvited')
            ]);
        }
    }

    /**
    * Deletes the recipient for the given survey (AJAX call).
    */
    public function deleteRecipient(Request $request, $link)
    {
        $inviter = \App\Models\SurveyCandidate::where('link', '=', $link)->first();
        $survey = $inviter->survey;
        $owner = $survey->ownerId;

        //Check if exists
        if ($inviter == null || !$this->isAdmin($survey->owner)) {
            return response()->json([
                'success' => false
            ]);
        }

        $survey = $inviter->survey;

        $this->validate($request, [
            'recipientId' => 'required|integer'
        ]);

        $surveyRecipient = $survey->recipients()
            ->where('recipientId', '=', $request->recipientId)
            ->where('invitedById', '=', $inviter->recipientId)
            ->first();

        if ($surveyRecipient != null) {
            //Check if allowed
            if ($inviter->recipientId === $surveyRecipient->invitedById
                && !$surveyRecipient->hasAnswered) {
                $surveyRecipient->delete();
                return response()->json([
                    'success' => true
                ]);
            }
        }

        return response()->json([
            'success' => false
        ]);
    }
}
