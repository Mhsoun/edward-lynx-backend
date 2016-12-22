<?php namespace App\Models;

use UnexpectedValueException;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as BaseUser;
// use Illuminate\Auth\Authenticatable;
// use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
// use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Lang;
use App\Models\DefaultText;

/**
* Represents a user
*/
class User extends BaseUser implements AuthorizableContract
{
    use Authorizable, HasApiTokens, Notifiable;

    /**
     * Indicates what each access level is.
     *
     * @var array
     */
    const ACCESS_LEVELS = [
        0 => null,
        1 => 'superadmin',
        2 => 'admin',
        3 => 'supervisor',
        4 => 'participant',
        5 => 'feedback-provider',
        6 => 'analyst'
    ];

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'email', 'info', 'password'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    protected $attributes = [
        'isAdmin' => false,
        'navColor' => ''
    ];

    /**
    * Indicates if the given user is an admin
    */
    public static function isEdwardLynx($id)
    {
        if (User::find($id)->isAdmin == 1) {
            return true;
        }

        return false;
    }

    /**
    * Returns the default emails
    */
    private function defaultEmails()
    {
        return $this->hasMany('\App\Models\DefaultText', 'ownerId');
    }

    /**
    * Finds the given default text
    */
    public function findDefaultText($type, $surveyType, $lang)
    {
        return $this->defaultEmails()
            ->where('type', '=', $type)
            ->where('surveyType', '=', $surveyType)
            ->where('lang', '=', $lang)
            ->first();
    }

    /**
    * Gets the default email for the given survey type & lang, or creates if it does not exist
    */
    private function getOrCreateDefaultEmail($type, $surveyType, $lang, $subject, $message)
    {
        $defaultEmail = $this->findDefaultText($type, $surveyType, $lang);

        if ($defaultEmail == null) {
            $defaultEmail = new DefaultText;
            $defaultEmail->type = $type;
            $defaultEmail->surveyType = $surveyType;
            $defaultEmail->lang = $lang;
            $defaultEmail->subject = $subject;
            $defaultEmail->text = $message;
            $this->defaultEmails()->save($defaultEmail);
        }

        return (object)[
            'type' => $type,
            'subject' => $defaultEmail->subject,
            'message' => $defaultEmail->text
        ];
    }

    /**
    * Gets the default text for the given survey type & lang, or creates if it does not exist
    */
    private function getOrCreateDefaultText($type, $surveyType, $lang, $text)
    {
        $defaultText = $this->findDefaultText($type, $surveyType, $lang);

        if ($defaultText == null) {
            $defaultText = new DefaultText;
            $defaultText->type = $type;
            $defaultText->surveyType = $surveyType;
            $defaultText->lang = $lang;
            $defaultText->text = $text;
            $this->defaultEmails()->save($defaultText);
        }

        return (object)[
            'type' => $type,
            'text' => $defaultText->text
        ];
    }

    /**
    * Gets the default email for the given survey type & lang, or creates if it does not exist based on the given language strings
    */
    private function getOrCreateDefaultEmailLangStrings($type, $surveyType, $lang, $subjectLangString, $messageLanguageString)
    {
        return $this->getOrCreateDefaultEmail(
            $type,
            $surveyType,
            $lang,
            Lang::get($subjectLangString, [], $lang),
            Lang::get($messageLanguageString, [], $lang));
    }

    /**
    * Gets the default text for the given survey type & lang, or creates if it does not exist based on the given language string
    */
    private function getOrCreateDefaultTextLangString($type, $surveyType, $lang, $textLangString)
    {
        return $this->getOrCreateDefaultText(
            $type,
            $surveyType,
            $lang,
            Lang::get($textLangString, [], $lang));
    }

    /**
    * Returns the default invitation email
    */
    public function defaultInvitationEmail($surveyType, $lang)
    {
        return $this->getOrCreateDefaultEmailLangStrings(
            DefaultText::InviteEmail,
            $surveyType,
            $lang,
            'surveys.inviteMailDefaultSubject',
            'surveys.inviteMailDefaultText');
    }

    /**
    * Returns the default reminder email
    */
    public function defaultReminderEmail($surveyType, $lang)
    {
        return $this->getOrCreateDefaultEmailLangStrings(
            DefaultText::ReminderEmail,
            $surveyType,
            $lang,
            'surveys.manualRemindingDefaultSubject',
            'surveys.manualRemindingDefaultText');
    }

    /**
    * Returns the default to evaluate email
    */
    public function defaultToEvaluateEmail($surveyType, $lang)
    {
        return $this->getOrCreateDefaultEmailLangStrings(
            DefaultText::InviteOthersEmail,
            $surveyType,
            $lang,
            'surveys.toEvaluateEmailDefaultSubject',
            'surveys.toEvaluateEmailDefaultText');
    }

    /**
    * Returns the default team invitation email
    */
    public function defaultTeamInvitationEmail($surveyType, $lang)
    {
        return $this->getOrCreateDefaultEmailLangStrings(
            DefaultText::InviteEvaluatedTeamEmail,
            $surveyType,
            $lang,
            'surveys.inviteTeamMailDefaultSubject',
            'surveys.inviteTeamMailDefaultText');
    }

    /**
    * Returns the default candidate invitation email
    */
    public function defaultCandidateInvitationEmail($surveyType, $lang)
    {
        return $this->getOrCreateDefaultEmailLangStrings(
            DefaultText::InviteCandidateEmail,
            $surveyType,
            $lang,
            'surveys.inviteCandidateMailDefaultSubject',
            'surveys.inviteCandidateMailDefaultText');
    }

    /**
    * Returns the default user report emails
    */
    public function defaultUserReportEmail($surveyType, $lang)
    {
        return $this->getOrCreateDefaultEmailLangStrings(
            DefaultText::UserReportEmail,
            $surveyType,
            $lang,
            'surveys.userReportMailDefaultSubject',
            'surveys.userReportMailDefaultText');
    }

    /**
    * Returns the default invite remnding emails
    */
    public function defaultInviteRemindingEmail($surveyType, $lang)
    {
        return $this->getOrCreateDefaultEmailLangStrings(
            DefaultText::InviteRemindingMail,
            $surveyType,
            $lang,
            'surveys.inviteRemindingMailDefaultSubject',
            'surveys.inviteRemindingMailDefaultText');
    }

    /**
    * Returns the default information text
    */
    public function defaultInformationText($surveyType, $lang)
    {
        $langString = "";

        if (\App\SurveyTypes::isIndividualLike($surveyType)) {
            $langString = 'surveys.default360Description';
        }

        if (\App\SurveyTypes::isGroupLike($surveyType)) {
            $langString = 'surveys.defaultLMTTDescription';
        }

        return $this->getOrCreateDefaultTextLangString(
            DefaultText::ToAnswerDescription,
            $surveyType,
            $lang,
            $langString);
    }

    /**
    * Returns the default invite others information text
    */
    public function defaultInviteOthersInformationText($surveyType, $lang)
    {
        return $this->getOrCreateDefaultTextLangString(
            DefaultText::ToInviteDescription,
            $surveyType,
            $lang,
            '');
    }

    /**
    * Returns the default thank you text
    */
    public function defaultThankYouText($surveyType, $lang)
    {
        return $this->getOrCreateDefaultTextLangString(
            DefaultText::ThankYouText,
            $surveyType,
            $lang,
            'surveys.surveyAnsweredText');
    }

    /**
    * Returns the default question info text
    */
    public function defaultQuestionInfoText($surveyType, $lang)
    {
        return $this->getOrCreateDefaultTextLangString(
            DefaultText::QuestionInfoText,
            $surveyType,
            $lang,
            '');
    }

    /**
    * Returns the default intro page report text
    */
    public function defaultIntroPageReportText($surveyType, $lang)
    {
        return $this->getOrCreateDefaultTextLangString(
            DefaultText::IntroReportText,
            $surveyType,
            $lang,
            'report.introPageText');
    }

    /**
    * Returns the default main title text
    */
    public function defaultMainTitleReportText($surveyType, $lang)
    {
        $textString = "";

        if (\App\SurveyTypes::isIndividualLike($surveyType)) {
            $textString = "report.mainTitleTextIndividual";
        } else if (\App\SurveyTypes::isGroupLike($surveyType)) {
            $textString = "report.mainTitleTextGroup";
        } else if ($surveyType == \App\SurveyTypes::Normal) {
            $textString = "report.mainTitleTextNormal";
        }

        return $this->getOrCreateDefaultTextLangString(
            DefaultText::MainTitleReportText,
            $surveyType,
            $lang,
            $textString);
    }

    /**
    * Returns the default footer report text
    */
    public function defaultFooterReportText($surveyType, $lang)
    {
        return $this->getOrCreateDefaultTextLangString(
            DefaultText::FooterReportText,
            $surveyType,
            $lang,
            'report.footerText');
    }

    /**
    * Returns the default answered report text
    */
    public function defaultAnsweredReportText($surveyType, $lang)
    {
        return $this->getOrCreateDefaultTextLangString(
            DefaultText::NumAnsweredReportText,
            $surveyType,
            $lang,
            'report.answeredText');
    }

    /**
    * Returns the default invited report text
    */
    public function defaultInvitedReportText($surveyType, $lang)
    {
        return $this->getOrCreateDefaultTextLangString(
            DefaultText::NumInvitedReportText,
            $surveyType,
            $lang,
            'report.invitedText');
    }

    /**
    * Returns the default average report text
    */
    public function defaultAverageReportText($surveyType, $lang)
    {
        $headingLangString = "report.averageValues";
        $textLangString = "report.averageValuesText";

        if ($surveyType == \App\SurveyTypes::Progress) {
            $headingLangString = "report.progressAverage";
            $textLangString = "report.progressAverageText";
        }

        return $this->getOrCreateDefaultEmailLangStrings(
            DefaultText::AverageReportText,
            $surveyType,
            $lang,
            $headingLangString,
            $textLangString);
    }

    /**
    * Returns the default blindspots report text
    */
    public function defaultBlindspotsReportText($surveyType, $lang)
    {
        return $this->getOrCreateDefaultEmailLangStrings(
            DefaultText::BlindspotsReportText,
            $surveyType,
            $lang,
            'report.blindspots',
            'report.blindspotsText');
    }

    /**
    * Returns the default blindspots over report text
    */
    public function defaultBlindspotsOverReportText($surveyType, $lang)
    {
        return $this->getOrCreateDefaultEmailLangStrings(
            DefaultText::BlindspotsOverReportText,
            $surveyType,
            $lang,
            'report.overestimated',
            'report.overestimatedText');
    }

    /**
    * Returns the default blindspots under report text
    */
    public function defaultBlindspotsUnderReportText($surveyType, $lang)
    {
        return $this->getOrCreateDefaultEmailLangStrings(
            DefaultText::BlindspotsUnderReportText,
            $surveyType,
            $lang,
            'report.underestimated',
            'report.underestimatedText');
    }

    /**
    * Returns the default breakdown report text
    */
    public function defaultBreakdownReportText($surveyType, $lang)
    {
        return $this->getOrCreateDefaultEmailLangStrings(
            DefaultText::BreakdownReportText,
            $surveyType,
            $lang,
            'report.breakdown',
            'report.breakdownText');
    }

    /**
    * Returns the default commentst report text
    */
    public function defaultCommentsReportText($surveyType, $lang)
    {
        return $this->getOrCreateDefaultEmailLangStrings(
            DefaultText::CommentsReportText,
            $surveyType,
            $lang,
            'report.comments',
            'report.commentsText');
    }

    /**
    * Returns the default detailed answer summary report text
    */
    public function defaultDetailedAnswerSummaryReportText($surveyType, $lang)
    {
        return $this->getOrCreateDefaultEmailLangStrings(
            DefaultText::DetailedAnswerSummaryReportText,
            $surveyType,
            $lang,
            'report.detailed',
            'report.detailedText');
    }

    /**
    * Returns the default highest report text for the given role
    */
    public function defaultHighestReportText($surveyType, $lang, $roleId)
    {
        return $this->getOrCreateDefaultEmailLangStrings(
            DefaultText::HighestReportText + $roleId,
            $surveyType,
            $lang,
            'report.highestRated',
            'report.highestRatedText');
    }

    /**
    * Returns the default lowest report text for the given role
    */
    public function defaultLowestReportText($surveyType, $lang, $roleId)
    {
        return $this->getOrCreateDefaultEmailLangStrings(
            DefaultText::LowestReportText + $roleId,
            $surveyType,
            $lang,
            'report.lowestRated',
            'report.lowestRatedText');
    }

    /**
    * Returns the default index over competencies report text
    */
    public function defaultIndexOverCompetenciesReportText($surveyType, $lang)
    {
        return $this->getOrCreateDefaultEmailLangStrings(
            DefaultText::IndexOverCompetenciesReportText,
            $surveyType,
            $lang,
            'report.indexOverCompetencies',
            'report.indexOverCompetenciesText');
    }

    /**
    * Returns the default participants report text
    */
    public function defaultParticipantsReportText($surveyType, $lang)
    {
        return $this->getOrCreateDefaultEmailLangStrings(
            DefaultText::ParticipantsReportText,
            $surveyType,
            $lang,
            'surveys.participants',
            'report.participantsText');
    }

    /**
    * Returns the default radar report text
    */
    public function defaultRadarReportText($surveyType, $lang)
    {
        return $this->getOrCreateDefaultEmailLangStrings(
            DefaultText::RadarReportText,
            $surveyType,
            $lang,
            'report.radarDiagram',
            'report.radarDiagramText');
    }

    /**
    * Returns the default ratings per question report text
    */
    public function defaultRatingsPerQuestionReportText($surveyType, $lang)
    {
        return $this->getOrCreateDefaultEmailLangStrings(
            DefaultText::RatingsPerQuestionReportText,
            $surveyType,
            $lang,
            'report.allQuestions',
            'report.allQuestionsText');
    }

    /**
    * Returns the default selected question report text
    */
    public function defaultSelectedQuestionReportText($surveyType, $lang)
    {
        return $this->getOrCreateDefaultEmailLangStrings(
            DefaultText::SelectedQuestionReportText,
            $surveyType,
            $lang,
            'report.selectedQuestions',
            'report.selectedQuestionsText');
    }

    /**
    * Returns the default yes or no report text
    */
    public function defaultYesOrNoReportText($surveyType, $lang)
    {
        return $this->getOrCreateDefaultEmailLangStrings(
            DefaultText::YesOrNoReportText,
            $surveyType,
            $lang,
            'report.yesOrNo',
            'report.yesOrNoText');
    }

    /**
    * Returns the progress radar report text
    */
    public function defaultProgressRadarReportText($surveyType, $lang)
    {
        return $this->getOrCreateDefaultEmailLangStrings(
            DefaultText::ProgressRadarReportText,
            $surveyType,
            $lang,
            'report.progressRadar',
            'report.progressRadarText');
    }

    /**
    * Returns the reponse rate radar report text
    */
    public function defaultResponseRateReportText($surveyType, $lang)
    {
        return $this->getOrCreateDefaultEmailLangStrings(
            DefaultText::ResponseRateReportText,
            $surveyType,
            $lang,
            'report.responseRate',
            'report.responseRateText');
    }

    /**
    * Returns the progress roles report text
    */
    public function defaultProgressRatingsPerRoleReportText($surveyType, $lang)
    {
        return $this->getOrCreateDefaultEmailLangStrings(
            DefaultText::ProgressRatingsPerRoleReportText,
            $surveyType,
            $lang,
            'report.progressRatingsPerRole',
            'report.progressRatingsPerRoleText');
    }

    /**
    * Returns the default group highest report text
    */
    public function defaultGroupHighestReportText($surveyType, $lang)
    {
        return $this->getOrCreateDefaultEmailLangStrings(
            DefaultText::GroupHighestReportText,
            $surveyType,
            $lang,
            'report.highestRated',
            'report.highestRatedText');
    }

    /**
    * Returns the default group lowest report text
    */
    public function defaultGroupLowestReportText($surveyType, $lang)
    {
        return $this->getOrCreateDefaultEmailLangStrings(
            DefaultText::GroupLowestReportText,
            $surveyType,
            $lang,
            'report.lowestRated',
            'report.lowestRatedText');
    }

    /**
    * Returns the default results per category report text
    */
    public function defaultResultsPerCategoryReportText($surveyType, $lang)
    {
        return $this->getOrCreateDefaultEmailLangStrings(
            DefaultText::ResultsPerCategoryReportText,
            $surveyType,
            $lang,
            'report.resultsPerCategory',
            'report.resultsPerCategoryText');
    }

    /**
    * Returns the default results per extra question report text
    */
    public function defaultResultsPerExtraQuestionReportText($surveyType, $lang)
    {
        return $this->getOrCreateDefaultEmailLangStrings(
            DefaultText::ResultsPerExtraQuestionReportText,
            $surveyType,
            $lang,
            'report.resultsPerExtraQuestion',
            'report.resultsPerExtraQuestionText');
    }

    /**
     * Returns TRUE if the current user has the provided access level.
     *
     * @param  string $accessLevel
     * @return boolean
     */
    public function isA($accessLevel)
    {
        $level = User::ACCESS_LEVELS[$this->access_level];
        return $level === $accessLevel;
    }

    /**
     * Alias of isA() method for readability.
     * 
     * @param string $accessLevel
     * @return boolean
     */
    public function isAn($accessLevel)
    {
        return $this->isA($accessLevel);
    }
}
