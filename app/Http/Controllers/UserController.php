<?php namespace App\Http\Controllers;

use Mail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\DefaultText;

/**
* Represents a user controller
*/
class UserController extends Controller
{
    private $languages = ['en' => 'English', 'sv' => 'Svenska', 'fi' => 'Suomi'];

     /**
     * The password broker implementation.
     *
     * @var PasswordBroker
     */
    protected $passwords;

    /**
     * Create a new user controller instance.
     *
     * @param  \Illuminate\Contracts\Auth\PasswordBroker  $passwords
     * @return void
     */
    public function __construct(PasswordBroker $passwords)
    {
        $this->passwords = $passwords;

        Validator::extend('validLanguage', function ($attribute, $value, $parameters) {
            return array_key_exists($value, $this->languages);
        }, 'The selected language is not valid.');

        Validator::extend('isCurrentPassword', function ($attribute, $value, $parameters) {
            return Hash::check($value, Auth::user()->password);
        }, 'The :attribute does not match the current password.');
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        if (Auth::user()->isAdmin) {
            $users = User::all();
            return view('user.admin.welcome', compact('users'));
        } else {
            $surveys = \App\Models\Survey::where('ownerId', '=', Auth::user()->id)->get();
            $activeSurveys = [];
            $timeNow = \Carbon\Carbon::now(\App\Models\Survey::TIMEZONE);

            foreach ($surveys as $survey) {
                $survey->numCompleted = $survey->recipients()
                    ->where('hasAnswered', '=', true)
                    ->count();

                if ($timeNow->lt($survey->endDate)) {
                    array_push($activeSurveys, $survey);
                }
            }

            return view('user.normal.welcome', compact('activeSurveys'));
        }
    }

    /**
     * Shows the login screen
     */
    public function login()
    {
        if (Auth::guest()) {
            return view('auth.login');
        } else {
            return redirect('home');
        }
    }

    /**
    * The setup password view
    */
    public function setupPasswordView(Request $request, $token)
    {
        $passwordSetup = \App\Models\PasswordSetup::where('token', '=', $token)
            ->first();

        if ($passwordSetup == null) {
            return view('company.invalidPasswordLink');
        }

        return view('company.setupPassword', compact('token'));
    }

    /**
    * Setups the password
    */
    public function setupPassword(Request $request, $token)
    {
        $this->validate($request, [
            'password' => 'required',
            'passwordConfirmation' => 'required|same:password',
        ]);

        $passwordSetup = \App\Models\PasswordSetup::where('token', '=', $token)
            ->first();

        if ($passwordSetup == null) {
            return view('company.invalidPasswordLink');
        }

        $user = $passwordSetup->user;
        $user->password = bcrypt($request->password);
        $passwordSetup->delete();
        $user->save();

        return redirect(action('UserController@login'));
    }

    /**
    * Returns the settigns view
    */
    public function settings()
    {
        return view('user.normal.settings')->with('languages', $this->languages);
    }

    /**
     * Updates the password
     */
    public function updatePassword(Request $request)
    {
        $this->validate($request, [
            'currentPassword' => 'required|isCurrentPassword',
            'newPassword' => 'required|min:6',
            'newPasswordAgain' => 'required|same:newPassword'
        ]);

        $user = Auth::user();
        $user->password = bcrypt($request->newPassword);
        $user->update();

        Session::flash('changeText', Lang::get('settings.passwordChangedText'));
        return redirect(action('UserController@settings'));
    }

    /**
    * Updates the language
    */
    public function updateLanguage(Request $request)
    {
        $this->validate($request, [
            'language' => 'required|validLanguage'
        ]);

        $user = Auth::user();
        $user->lang = $request->language;
        $user->update();

        Session::flash('changeText', Lang::get('settings.languageChangedText'));
        return redirect(action('UserController@settings'));
    }

    /**
    * Updates the given email texts
    */
    private function updateEmailTexts($user, $emails, $request)
    {
        foreach ($emails as $email) {
            foreach ($this->languages as $langId => $langName) {
                foreach (\App\SurveyTypes::all() as $surveyType) {
                    $subject = $request['email_' . $email . '_subject_' . $surveyType . '_' . $langId];
                    $message = $request['email_' . $email . '_message_' . $surveyType . '_' . $langId];

                    if ($subject !== null && $message !== null) {
                        $defaultEmail = $user->findDefaultText($email, $surveyType, $langId);

                        if ($defaultEmail != null) {
                            $defaultEmail->subject = $subject;
                            $defaultEmail->text = $message;
                            $defaultEmail->save();
                        }
                    }
                }
            }
        }
    }

    /**
    * Updates the given texts
    */
    private function updateTexts($user, $texts, $request)
    {
        foreach ($texts as $currentText) {
            foreach ($this->languages as $langId => $langName) {
                foreach (\App\SurveyTypes::all() as $surveyType) {
                    $text = $request['text_' . $currentText . '_' . $surveyType . '_' . $langId];

                    if ($text !== null) {
                        $defaultText = $user->findDefaultText($currentText, $surveyType, $langId);

                        if ($defaultText != null) {
                            $defaultText->text = $text;
                            $defaultText->save();
                        }
                    }
                }
            }
        }
    }

    /**
     * Updates the default emails
     */
    public function updateEmails(Request $request)
    {
        $user = Auth::user();

        $emails = [
            DefaultText::InviteEmail,
            DefaultText::ReminderEmail,
            DefaultText::InviteOthersEmail,
            DefaultText::InviteEvaluatedTeamEmail,
            DefaultText::InviteCandidateEmail,
            DefaultText::UserReportEmail,
            DefaultText::InviteRemindingMail,
        ];

        $this->updateEmailTexts($user, $emails, $request);
        $user->update();

        Session::flash('changeText', Lang::get('settings.defaultEmailsChangedText'));
        return redirect(action('UserController@settings'));
    }

    /**
     * Updates the default informations
     */
    public function updateInformations(Request $request)
    {
        $user = Auth::user();

        $texts = [
            DefaultText::ToAnswerDescription,
            DefaultText::ToInviteDescription,
            DefaultText::ThankYouText,
            DefaultText::QuestionInfoText,
        ];

        $this->updateTexts($user, $texts, $request);
        $user->update();

        Session::flash('changeText', Lang::get('settings.defaultInformationsChangedText'));
        return redirect(action('UserController@settings'));
    }

    /**
     * Updates the default report texts
     */
    public function updateReportTexts(Request $request)
    {
        $user = Auth::user();

        $surveyTexts = [
            DefaultText::IntroReportText,
            DefaultText::MainTitleReportText,
            DefaultText::FooterReportText,
            DefaultText::NumInvitedReportText,
            DefaultText::NumAnsweredReportText,
        ];

        //Survey texts
        $this->updateTexts($user, $surveyTexts, $request);

        $reportTexts = [
            DefaultText::AverageReportText,
            DefaultText::BlindspotsReportText,
            DefaultText::BlindspotsOverReportText,
            DefaultText::BlindspotsUnderReportText,
            DefaultText::BreakdownReportText,
            DefaultText::CommentsReportText,
            DefaultText::DetailedAnswerSummaryReportText,
            DefaultText::HighestReportText,
            DefaultText::LowestReportText,
            DefaultText::IndexOverCompetenciesReportText,
            DefaultText::ParticipantsReportText,
            DefaultText::RadarReportText,
            DefaultText::RatingsPerQuestionReportText,
            DefaultText::SelectedQuestionReportText,
            DefaultText::YesOrNoReportText,
            DefaultText::ProgressRadarReportText,
            DefaultText::ResponseRateReportText,
            DefaultText::ProgressRatingsPerRoleReportText,
            DefaultText::GroupHighestReportText,
            DefaultText::GroupLowestReportText,
            DefaultText::ResultsPerCategoryReportText,
        ];

        foreach (\App\Roles::get360() as $role) {
            array_push($reportTexts, DefaultText::HighestReportText + $role->id);
            array_push($reportTexts, DefaultText::LowestReportText + $role->id);
        }

        array_push($reportTexts, DefaultText::HighestReportText - 1);
        array_push($reportTexts, DefaultText::LowestReportText - 1);

        //Diagram texts
        $this->updateEmailTexts($user, $reportTexts, $request);
        $user->update();

        Session::flash('changeText', Lang::get('settings.defaultReportTextChangedText'));
        return redirect(action('UserController@settings'));
    }

    /**
     * Updates the default default texts
     */
    public function updateDefaultTexts(Request $request)
    {
        $user = Auth::user();

        $texts = [
            DefaultText::IntroReportText,
            DefaultText::MainTitleReportText,
            DefaultText::FooterReportText,
            DefaultText::NumInvitedReportText,
            DefaultText::NumAnsweredReportText,
            DefaultText::ToAnswerDescription,
            DefaultText::ToInviteDescription,
            DefaultText::ThankYouText,
            DefaultText::QuestionInfoText,
        ];

        $this->updateTexts($user, $texts, $request);

        $emailTexts = [
            DefaultText::AverageReportText,
            DefaultText::BlindspotsReportText,
            DefaultText::BlindspotsOverReportText,
            DefaultText::BlindspotsUnderReportText,
            DefaultText::BreakdownReportText,
            DefaultText::CommentsReportText,
            DefaultText::DetailedAnswerSummaryReportText,
            DefaultText::HighestReportText,
            DefaultText::LowestReportText,
            DefaultText::IndexOverCompetenciesReportText,
            DefaultText::ParticipantsReportText,
            DefaultText::RadarReportText,
            DefaultText::RatingsPerQuestionReportText,
            DefaultText::SelectedQuestionReportText,
            DefaultText::YesOrNoReportText,
            DefaultText::ProgressRadarReportText,
            DefaultText::ResponseRateReportText,
            DefaultText::ProgressRatingsPerRoleReportText,
            DefaultText::GroupHighestReportText,
            DefaultText::GroupLowestReportText,
            DefaultText::ResultsPerCategoryReportText,
            DefaultText::InviteEmail,
            DefaultText::ReminderEmail,
            DefaultText::InviteOthersEmail,
            DefaultText::InviteEvaluatedTeamEmail,
            DefaultText::InviteCandidateEmail,
            DefaultText::UserReportEmail,
            DefaultText::InviteRemindingMail,
            DefaultText::ResultsPerExtraQuestionReportText
        ];

        foreach (\App\Roles::get360() as $role) {
            array_push($emailTexts, DefaultText::HighestReportText + $role->id);
            array_push($emailTexts, DefaultText::LowestReportText + $role->id);
        }

        array_push($emailTexts, DefaultText::HighestReportText - 1);
        array_push($emailTexts, DefaultText::LowestReportText - 1);

        $this->updateEmailTexts($user, $emailTexts, $request);
        $user->update();

        Session::flash('changeText', Lang::get('settings.defaultReportTextChangedText'));
        return redirect(action('UserController@settings'));
    }
}
