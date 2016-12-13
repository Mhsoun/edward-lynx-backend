<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Lang;
use Auth;

/**
* Represents a default text for emails/descriptions
*/
class DefaultText extends Model
{
	/**
	* The database table used by the model
	*/
	protected $table = 'default_texts';

	public $timestamps = false;

	const InviteEmail = 0;
	const ReminderEmail = 1;
	const InviteOthersEmail = 2;
	const InviteEvaluatedTeamEmail = 3;
	const InviteCandidateEmail = 4;
	const ToAnswerDescription = 5;
	const ToInviteDescription = 6;

	const AverageReportText = 7;
	const BlindspotsReportText = 8;
	const BlindspotsOverReportText = 9;
	const BlindspotsUnderReportText = 10;
	const BreakdownReportText = 11;
	const CommentsReportText = 12;
	const DetailedAnswerSummaryReportText = 13;
	const HighestReportText = 1000; //We add role id to this value
	const LowestReportText = 2000; //We add role id to this value
	const IndexOverCompetenciesReportText = 16;
	const ParticipantsReportText = 17;
	const RadarReportText = 18;
	const RatingsPerQuestionReportText = 19;
	const SelectedQuestionReportText = 20;
	const YesOrNoReportText = 21;
	const ProgressRadarReportText = 22;
	const ResponseRateReportText = 23;

	const IntroReportText = 24;
	const MainTitleReportText = 25;
	const FooterReportText = 26;
	const NumInvitedReportText = 27;
	const NumAnsweredReportText = 28;

	const ThankYouText = 29;
	const UserReportEmail = 30;
	const QuestionInfoText = 31;
	const InviteRemindingMail = 32;

	const ProgressRatingsPerRoleReportText = 33;
	const GroupHighestReportText = 34;
	const GroupLowestReportText = 35;
	const ResultsPerCategoryReportText = 36;
	const ResultsPerExtraQuestionReportText = 37;

	/**
	* Returns the default emails for the given user
	*/
	public static function defaultEmails($user)
	{
		$surveyTypeEmails = [];

		$emails = [];
		$emails[DefaultText::InviteEmail] = (object)[
			'header' => Lang::get('surveys.invitationEmail'),
			'getEmail' => function($id, $lang) use ($user) { return $user->defaultInvitationEmail($id, $lang); }
		];

		$emails[DefaultText::ReminderEmail] = (object)[
			'header' => Lang::get('surveys.remindingEmail'),
			'getEmail' => function($id, $lang) use ($user) { return $user->defaultReminderEmail($id, $lang); }
		];

		$emails[DefaultText::InviteOthersEmail] = (object)[
			'header' => Lang::get('surveys.toEvaluateEmail'),
			'getEmail' => function($id, $lang) use ($user) { return $user->defaultToEvaluateEmail($id, $lang); }
		];

		$emails[DefaultText::InviteCandidateEmail] = (object)[
			'header' => Lang::get('surveys.candidateInvitationEmail'),
			'getEmail' => function($id, $lang) use ($user) { return $user->defaultCandidateInvitationEmail($id, $lang); }
		];

		$emails[DefaultText::InviteEvaluatedTeamEmail] = (object)[
			'header' => Lang::get('surveys.teamInvitationEmail'),
			'getEmail' => function($id, $lang) use ($user) { return $user->defaultTeamInvitationEmail($id, $lang); }
		];

		$emails[DefaultText::UserReportEmail] = (object)[
			'header' => Lang::get('surveys.userReportEmail'),
			'getEmail' => function($id, $lang) use ($user) { return $user->defaultUserReportEmail($id, $lang); }
		];

		$emails[DefaultText::InviteRemindingMail] = (object)[
			'header' => Lang::get('surveys.inviteRemindingMail'),
			'getEmail' => function($id, $lang) use ($user) { return $user->defaultInviteRemindingEmail($id, $lang); }
		];

	    if (\App\SurveyTypes::canCreateIndividual($user->allowedSurveyTypes) || $user->isAdmin) {
	        array_push($surveyTypeEmails, (object)[
	            'id' => \App\SurveyTypes::Individual,
	            'emails' => [
					$emails[DefaultText::InviteEmail],
					$emails[DefaultText::ReminderEmail],
					$emails[DefaultText::InviteOthersEmail],
					$emails[DefaultText::InviteCandidateEmail],
					$emails[DefaultText::InviteRemindingMail],
	            ]
	        ]);
	    }

	    if (\App\SurveyTypes::canCreateGroup($user->allowedSurveyTypes) || $user->isAdmin) {
	        array_push($surveyTypeEmails, (object)[
	            'id' => \App\SurveyTypes::Group,
	            'emails' => [
					$emails[DefaultText::InviteEmail],
					$emails[DefaultText::ReminderEmail],
					$emails[DefaultText::InviteEvaluatedTeamEmail]
	            ]
	        ]);
	    }

	    if (\App\SurveyTypes::canCreateProgress($user->allowedSurveyTypes) || $user->isAdmin) {
	        array_push($surveyTypeEmails, (object)[
	            'id' => \App\SurveyTypes::Progress,
	            'emails' => [
					$emails[DefaultText::InviteEmail],
					$emails[DefaultText::ReminderEmail],
					$emails[DefaultText::InviteOthersEmail],
					$emails[DefaultText::UserReportEmail],
					$emails[DefaultText::InviteRemindingMail],
	            ]
	        ]);
	    }

	    if (\App\SurveyTypes::canCreateLTT($user->allowedSurveyTypes) || $user->isAdmin) {
	        array_push($surveyTypeEmails, (object)[
	            'id' => \App\SurveyTypes::LTT,
	            'emails' => [
					$emails[DefaultText::InviteEmail],
					$emails[DefaultText::ReminderEmail],
					$emails[DefaultText::InviteEvaluatedTeamEmail]
	            ]
	        ]);
	    }

	    if (\App\SurveyTypes::canCreateNormal($user->allowedSurveyTypes) || $user->isAdmin) {
	        array_push($surveyTypeEmails, (object)[
	            'id' => \App\SurveyTypes::Normal,
	            'emails' => [
					$emails[DefaultText::InviteEmail],
					$emails[DefaultText::ReminderEmail],
	            ]
	        ]);
	    }

	    return $surveyTypeEmails;
	}

	/**
	* Returns the default informations for the given user
	*/
	public static function defaultInformations($user)
	{
		$surveyTypeInformations = [];

		$informations = [];
		$informations[DefaultText::ToAnswerDescription] = (object)[
			'header' => Lang::get('surveys.description'),
			'getText' => function($id, $lang) use ($user) { return $user->defaultInformationText($id, $lang); }
		];

		$informations[DefaultText::ToInviteDescription] = (object)[
			'header' => Lang::get('surveys.inviteTextLabel'),
			'getText' => function($id, $lang) use ($user) { return $user->defaultInviteOthersInformationText($id, $lang); }
		];

		$informations[DefaultText::ThankYouText] = (object)[
			'header' => Lang::get('surveys.thankYouText'),
			'getText' => function($id, $lang) use ($user) { return $user->defaultThankYouText($id, $lang); }
		];

		$informations[DefaultText::QuestionInfoText] = (object)[
			'header' => Lang::get('surveys.questionInfoText'),
			'getText' => function($id, $lang) use ($user) { return $user->defaultQuestionInfoText($id, $lang); }
		];

	    if (\App\SurveyTypes::canCreateIndividual($user->allowedSurveyTypes) || $user->isAdmin) {
	        array_push($surveyTypeInformations, (object)[
	            'id' => \App\SurveyTypes::Individual,
	            'texts' => [
					$informations[DefaultText::ToAnswerDescription],
					$informations[DefaultText::ToInviteDescription],
					$informations[DefaultText::ThankYouText],
					$informations[DefaultText::QuestionInfoText],
	            ]
	        ]);
	    }

	    if (\App\SurveyTypes::canCreateGroup($user->allowedSurveyTypes) || $user->isAdmin) {
	        array_push($surveyTypeInformations, (object)[
	            'id' => \App\SurveyTypes::Group,
	            'texts' => [
	                $informations[DefaultText::ToAnswerDescription],
					$informations[DefaultText::ThankYouText],
					$informations[DefaultText::QuestionInfoText],
	            ]
	        ]);
	    }

	    if (\App\SurveyTypes::canCreateProgress($user->allowedSurveyTypes) || $user->isAdmin) {
	        array_push($surveyTypeInformations, (object)[
	            'id' => \App\SurveyTypes::Progress,
	            'texts' => [
					$informations[DefaultText::ToAnswerDescription],
					$informations[DefaultText::ToInviteDescription],
					$informations[DefaultText::ThankYouText],
					$informations[DefaultText::QuestionInfoText],
	            ]
	        ]);
	    }

	    if (\App\SurveyTypes::canCreateLTT($user->allowedSurveyTypes) || $user->isAdmin) {
	        array_push($surveyTypeInformations, (object)[
	            'id' => \App\SurveyTypes::LTT,
	            'texts' => [
	                $informations[DefaultText::ToAnswerDescription],
					$informations[DefaultText::ThankYouText],
					$informations[DefaultText::QuestionInfoText],
	            ]
	        ]);
	    }

	    if (\App\SurveyTypes::canCreateNormal($user->allowedSurveyTypes) || $user->isAdmin) {
	        array_push($surveyTypeInformations, (object)[
	            'id' => \App\SurveyTypes::Normal,
	            'texts' => [
	                $informations[DefaultText::ToAnswerDescription],
					$informations[DefaultText::ThankYouText],
					$informations[DefaultText::QuestionInfoText],
	            ]
	        ]);
	    }

	    return $surveyTypeInformations;
	}

	/**
	* Returns the default report texts for the given user
	*/
	public static function defaultReportTexts($user)
	{
		$surveyTypeReportTexts = [];

		$introPageTexts = [];
		$introPageTexts[DefaultText::IntroReportText] = (object)[
			'header' => Lang::get('report.introPage'),
			'getText' => function($id, $lang) use ($user) { return $user->defaultIntroPageReportText($id, $lang); }
		];

		$introPageTexts[DefaultText::MainTitleReportText] = (object)[
			'header' => Lang::get('report.mainTitle'),
			'getText' => function($id, $lang) use ($user) { return $user->defaultMainTitleReportText($id, $lang); },
			'small' => true
		];

		$introPageTexts[DefaultText::FooterReportText] = (object)[
			'header' => Lang::get('report.footer'),
			'getText' => function($id, $lang) use ($user) { return $user->defaultFooterReportText($id, $lang); },
			'small' => true
		];

		$introPageTexts[DefaultText::NumAnsweredReportText] = (object)[
			'header' => Lang::get('report.answered'),
			'getText' => function($id, $lang) use ($user) { return $user->defaultAnsweredReportText($id, $lang); },
			'small' => true
		];

		$introPageTexts[DefaultText::NumInvitedReportText] = (object)[
			'header' => Lang::get('report.invited'),
			'getText' => function($id, $lang) use ($user) { return $user->defaultInvitedReportText($id, $lang); },
			'small' => true,
			'clear' => true
		];

		$diagramTexts = [];
		$diagramTexts[DefaultText::AverageReportText] = (object)[
			'header' => Lang::get('report.averageValues'),
			'getText' => function($id, $lang) use ($user) { return $user->defaultAverageReportText($id, $lang); }
		];

		$diagramTexts[DefaultText::BlindspotsReportText] = (object)[
			'header' => Lang::get('report.blindspots'),
			'getText' => function($id, $lang) use ($user) { return $user->defaultBlindspotsReportText($id, $lang); }
		];

		$diagramTexts[DefaultText::BlindspotsOverReportText] = (object)[
			'header' => Lang::get('report.overestimated'),
			'getText' => function($id, $lang) use ($user) { return $user->defaultBlindspotsOverReportText($id, $lang); }
		];

		$diagramTexts[DefaultText::BlindspotsUnderReportText] = (object)[
			'header' => Lang::get('report.underestimated'),
			'getText' => function($id, $lang) use ($user) { return $user->defaultBlindspotsUnderReportText($id, $lang); }
		];

		$diagramTexts[DefaultText::BreakdownReportText] = (object)[
			'header' => Lang::get('report.breakdown'),
			'getText' => function($id, $lang) use ($user) { return $user->defaultBreakdownReportText($id, $lang); }
		];

		$diagramTexts[DefaultText::CommentsReportText] = (object)[
			'header' => Lang::get('report.comments'),
			'getText' => function($id, $lang) use ($user) { return $user->defaultCommentsReportText($id, $lang); }
		];

		$diagramTexts[DefaultText::DetailedAnswerSummaryReportText] = (object)[
			'header' => Lang::get('report.detailed'),
			'getText' => function($id, $lang) use ($user) { return $user->defaultDetailedAnswerSummaryReportText($id, $lang); }
		];

		$highestForRole = function($roleId) use ($user) {
			return (object)[
				'header' => Lang::get('report.highestRated') . ' (' . \App\Roles::name($roleId) . ')',
				'getText' => function($id, $lang) use ($user, $roleId) { return $user->defaultHighestReportText($id, $lang, $roleId); }
			];
		};

		$lowestForRole = function($roleId) use ($user) {
			return (object)[
				'header' => Lang::get('report.lowestRated') . ' (' . \App\Roles::name($roleId) . ')',
				'getText' => function($id, $lang) use ($user, $roleId) { return $user->defaultLowestReportText($id, $lang, $roleId); }
			];
		};

		$diagramTexts[DefaultText::IndexOverCompetenciesReportText] = (object)[
			'header' => Lang::get('report.indexOverCompetencies'),
			'getText' => function($id, $lang) use ($user) { return $user->defaultIndexOverCompetenciesReportText($id, $lang); }
		];

		$diagramTexts[DefaultText::RadarReportText] = (object)[
			'header' => Lang::get('report.radarDiagram'),
			'getText' => function($id, $lang) use ($user) { return $user->defaultRadarReportText($id, $lang); }
		];

		$diagramTexts[DefaultText::SelectedQuestionReportText] = (object)[
			'header' => Lang::get('report.selectedQuestions'),
			'getText' => function($id, $lang) use ($user) { return $user->defaultSelectedQuestionReportText($id, $lang); }
		];

		$diagramTexts[DefaultText::YesOrNoReportText] = (object)[
			'header' => Lang::get('report.yesOrNo'),
			'getText' => function($id, $lang) use ($user) { return $user->defaultYesOrNoReportText($id, $lang); }
		];

		$diagramTexts[DefaultText::ResponseRateReportText] = (object)[
			'header' => Lang::get('report.responseRate'),
			'getText' => function($id, $lang) use ($user) { return $user->defaultResponseRateReportText($id, $lang); }
		];

		$diagramTexts[DefaultText::RatingsPerQuestionReportText] = (object)[
			'header' => Lang::get('report.allQuestions'),
			'getText' => function($id, $lang) use ($user) { return $user->defaultRatingsPerQuestionReportText($id, $lang); }
		];

		$diagramTexts[DefaultText::ProgressRadarReportText] = (object)[
			'header' => Lang::get('report.progressRadar'),
			'getText' => function($id, $lang) use ($user) { return $user->defaultProgressRadarReportText($id, $lang); }
		];

		$diagramTexts[DefaultText::ParticipantsReportText] = (object)[
			'header' => Lang::get('surveys.participants'),
			'getText' => function($id, $lang) use ($user) { return $user->defaultParticipantsReportText($id, $lang); }
		];

		$diagramTexts[DefaultText::ProgressRatingsPerRoleReportText] = (object)[
			'header' => Lang::get('report.progressRatingsPerRole'),
			'getText' => function($id, $lang) use ($user) { return $user->defaultProgressRatingsPerRoleReportText($id, $lang); }
		];

		$diagramTexts[DefaultText::GroupHighestReportText] = (object)[
			'header' => Lang::get('report.highestRated'),
			'getText' => function($id, $lang) use ($user) { return $user->defaultGroupHighestReportText($id, $lang); }
		];

		$diagramTexts[DefaultText::GroupLowestReportText] = (object)[
			'header' => Lang::get('report.lowestRated'),
			'getText' => function($id, $lang) use ($user) { return $user->defaultGroupLowestReportText($id, $lang); }
		];

		$diagramTexts[DefaultText::ResultsPerCategoryReportText] = (object)[
			'header' => Lang::get('report.resultsPerCategory'),
			'getText' => function($id, $lang) use ($user) { return $user->defaultResultsPerCategoryReportText($id, $lang); }
		];

		$diagramTexts[DefaultText::ResultsPerExtraQuestionReportText] = (object)[
			'header' => Lang::get('report.resultsPerExtraQuestion'),
			'getText' => function($id, $lang) use ($user) { return $user->defaultResultsPerExtraQuestionReportText($id, $lang); }
		];

	    if (\App\SurveyTypes::canCreateIndividual($user->allowedSurveyTypes) || $user->isAdmin) {
			$highestLowestTexts = [];
			foreach (\App\Roles::get360() as $role) {
				array_push($highestLowestTexts, $highestForRole($role->id));
				array_push($highestLowestTexts, $lowestForRole($role->id));
			}

			array_push($highestLowestTexts, $highestForRole(-1));
			array_push($highestLowestTexts, $lowestForRole(-1));

	        array_push($surveyTypeReportTexts, (object)[
	            'id' => \App\SurveyTypes::Individual,
	            'reportTexts' => array_flatten([
					$introPageTexts[DefaultText::IntroReportText],
					$introPageTexts[DefaultText::MainTitleReportText],
					$introPageTexts[DefaultText::FooterReportText],
					$introPageTexts[DefaultText::NumAnsweredReportText],
					$introPageTexts[DefaultText::NumInvitedReportText],
					$diagramTexts[DefaultText::ResponseRateReportText],
	                $diagramTexts[DefaultText::AverageReportText],
					$diagramTexts[DefaultText::IndexOverCompetenciesReportText],
	                $diagramTexts[DefaultText::BlindspotsReportText],
	                $diagramTexts[DefaultText::BlindspotsOverReportText],
	                $diagramTexts[DefaultText::BlindspotsUnderReportText],
	                $diagramTexts[DefaultText::BreakdownReportText],
	                $diagramTexts[DefaultText::CommentsReportText],
	                $diagramTexts[DefaultText::DetailedAnswerSummaryReportText],
					$highestLowestTexts,
	                $diagramTexts[DefaultText::RadarReportText],
	                $diagramTexts[DefaultText::SelectedQuestionReportText],
	                $diagramTexts[DefaultText::YesOrNoReportText],
	            ])
	        ]);
	    }

	    if (\App\SurveyTypes::canCreateGroup($user->allowedSurveyTypes) || $user->isAdmin) {
	        array_push($surveyTypeReportTexts, (object)[
	            'id' => \App\SurveyTypes::Group,
	            'reportTexts' => array_flatten([
					$introPageTexts[DefaultText::IntroReportText],
					$introPageTexts[DefaultText::MainTitleReportText],
					$introPageTexts[DefaultText::FooterReportText],
					$introPageTexts[DefaultText::NumAnsweredReportText],
					$introPageTexts[DefaultText::NumInvitedReportText],
					$diagramTexts[DefaultText::ResponseRateReportText],
	                $diagramTexts[DefaultText::AverageReportText],
					$diagramTexts[DefaultText::IndexOverCompetenciesReportText],
	                $diagramTexts[DefaultText::BlindspotsReportText],
	                $diagramTexts[DefaultText::BlindspotsOverReportText],
	                $diagramTexts[DefaultText::BlindspotsUnderReportText],
	                $diagramTexts[DefaultText::CommentsReportText],
	                $diagramTexts[DefaultText::DetailedAnswerSummaryReportText],
	                $diagramTexts[DefaultText::GroupHighestReportText],
	                $diagramTexts[DefaultText::GroupLowestReportText],
	                $diagramTexts[DefaultText::RadarReportText],
	                $diagramTexts[DefaultText::ResultsPerCategoryReportText],
	            ])
	        ]);
	    }

	    if (\App\SurveyTypes::canCreateProgress($user->allowedSurveyTypes) || $user->isAdmin) {
	        array_push($surveyTypeReportTexts, (object)[
	            'id' => \App\SurveyTypes::Progress,
	            'reportTexts' => [
					$introPageTexts[DefaultText::IntroReportText],
					$introPageTexts[DefaultText::MainTitleReportText],
					$introPageTexts[DefaultText::FooterReportText],
					$introPageTexts[DefaultText::NumAnsweredReportText],
					$introPageTexts[DefaultText::NumInvitedReportText],
					$diagramTexts[DefaultText::RatingsPerQuestionReportText],
					$diagramTexts[DefaultText::AverageReportText],
					$diagramTexts[DefaultText::ProgressRadarReportText],
					$diagramTexts[DefaultText::ProgressRatingsPerRoleReportText],
	            ]
	        ]);
	    }

	    if (\App\SurveyTypes::canCreateLTT($user->allowedSurveyTypes) || $user->isAdmin) {
	        array_push($surveyTypeReportTexts, (object)[
	            'id' => \App\SurveyTypes::LTT,
	            'reportTexts' => array_flatten([
					$introPageTexts[DefaultText::IntroReportText],
					$introPageTexts[DefaultText::MainTitleReportText],
					$introPageTexts[DefaultText::FooterReportText],
					$introPageTexts[DefaultText::NumAnsweredReportText],
					$introPageTexts[DefaultText::NumInvitedReportText],
					$diagramTexts[DefaultText::ResponseRateReportText],
	                $diagramTexts[DefaultText::AverageReportText],
					$diagramTexts[DefaultText::IndexOverCompetenciesReportText],
	                $diagramTexts[DefaultText::BlindspotsReportText],
	                $diagramTexts[DefaultText::BlindspotsOverReportText],
	                $diagramTexts[DefaultText::BlindspotsUnderReportText],
	                $diagramTexts[DefaultText::CommentsReportText],
	                $diagramTexts[DefaultText::DetailedAnswerSummaryReportText],
	                $diagramTexts[DefaultText::GroupHighestReportText],
	                $diagramTexts[DefaultText::GroupLowestReportText],
	                $diagramTexts[DefaultText::RadarReportText],
	                $diagramTexts[DefaultText::ResultsPerCategoryReportText],
	            ])
	        ]);
	    }

	    if (\App\SurveyTypes::canCreateNormal($user->allowedSurveyTypes) || $user->isAdmin) {
	        array_push($surveyTypeReportTexts, (object)[
	            'id' => \App\SurveyTypes::Normal,
	            'reportTexts' => [
					$introPageTexts[DefaultText::IntroReportText],
					$introPageTexts[DefaultText::MainTitleReportText],
					$introPageTexts[DefaultText::FooterReportText],
					$introPageTexts[DefaultText::NumAnsweredReportText],
					$introPageTexts[DefaultText::NumInvitedReportText],
					$diagramTexts[DefaultText::AverageReportText],
					$diagramTexts[DefaultText::RatingsPerQuestionReportText],
					$diagramTexts[DefaultText::ResultsPerCategoryReportText],
					$diagramTexts[DefaultText::ResultsPerExtraQuestionReportText],
					$diagramTexts[DefaultText::ParticipantsReportText]
	            ]
	        ]);
	    }

	    return $surveyTypeReportTexts;
	}

	/**
	* Returns the default report texts for the given user and survey type
	*/
	public static function defaultReportTextsFor($user, $surveyType)
	{
		$defaultReportTexts = DefaultText::defaultReportTexts($user);

		foreach ($defaultReportTexts as $surveyTypeTexts) {
			if ($surveyTypeTexts->id == $surveyType) {
				return $surveyTypeTexts;
			}
		}

		return null;
	}

	/**
	* Returns the given report text either from user default or the given report template
	*/
	private static function getReportTextInternal($survey, $lang, $reportTemplate, $defaultText)
	{
		if ($reportTemplate != null) {
            $templateDiagram = $reportTemplate->diagrams()
                ->where('typeId', '=', $defaultText->type)
                ->first();

			if ($templateDiagram != null) {
	            if ($templateDiagram->isDiagram) {
	                return (object)[
	                    'type' => $defaultText->type,
	                    'subject' => $templateDiagram->title,
	                    'message' => $templateDiagram->text
	                ];
	            } else {
	                return (object)[
	                    'type' => $defaultText->type,
	                    'text' => $templateDiagram->text
	                ];
	            }
			}
        }

		return $defaultText;
	}

	/**
	* Returns the given report text either from user default or the given report template
	*/
    public static function getReportText($survey, $lang, $reportTemplate, $name)
    {
        //We use this to get the type id
		$user = $survey->owner;

		if (!Auth::guest()) {
			$user = Auth::user();
		}

		$defaultText = $user->{$name}($survey->type, $lang);
        return DefaultText::getReportTextInternal($survey, $lang, $reportTemplate, $defaultText);
    }

	/**
	* Returns the given report text for the given role either from user default or the given report template
	*/
    public static function getReportTextForRole($survey, $lang, $reportTemplate, $name, $roleId)
    {
        //We use this to get the type id
		$user = $survey->owner;

		if (!Auth::guest()) {
			$user = Auth::user();
		}

		$defaultText = $user->{$name}($survey->type, $lang, $roleId);
		return DefaultText::getReportTextInternal($survey, $lang, $reportTemplate, $defaultText);
    }
}
