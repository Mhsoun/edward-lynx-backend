<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Roles;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\SurveyTypes;
use App\Models\Survey;
use App\SurveyReportHelpers;
use App\Models\SurveyCandidateSharedReport;

/**
* Represents a controller for creating and viewing reports
*/
class ReportController extends Controller
{
	/**
     * Indicates if the auth user can edit the given survey
     */
     private function canEditSurvey($survey)
     {
         return \App\Surveys::canEditSurvey($survey);
     }

    /**
    * Returns the report path
    */
    private function reportPath($fileName = null)
    {
        $reportDir = public_path() . DIRECTORY_SEPARATOR . 'reports';

        if ($fileName != null) {
            return $reportDir . DIRECTORY_SEPARATOR . $fileName;
        } else {
            return $reportDir;
        }
    }

    /**
    * Views a created report
    */
    public function viewReport(Request $request, $id)
    {
        $surveyReport = \App\Models\SurveyReportFile::find($id);

        if ($surveyReport == null) {
            return redirect(action('SurveyController@index'));
        }

        $survey = $surveyReport->survey;

        if (!$this->canEditSurvey($survey)) {
            return redirect(action('SurveyController@index'));
        }

        return response(file_get_contents($this->reportPath($surveyReport->fileName)))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="'. $surveyReport->fileName . '"');
    }

    /**
    * Includes the given file on the server
    */
    private function getIncludeContents($filename)
    {
        if (is_file($filename)) {
            ob_start();
            include $filename;
            $contents = ob_get_contents();
            ob_end_clean();
            return $contents;
        }

        return false;
    }

    /**
    * Sets the language based on the request
    */
    private function setLanguage($survey, $request)
    {
        return $this->setLanguageFromName($survey, $request->lang);
    }

    /**
    * Sets the language based on the given name
    */
    private function setLanguageFromName($survey, $lang)
    {
        if (!($lang != "" && \App\Languages::isValid($lang))) {
            $lang = $survey->lang;
        }

        app()->setLocale($lang);
        return $lang;
    }

    /**
    * Extracts the constraints from the given request
    */
    private function extractConstraints(Request $request, $survey)
    {
        $constraints = [];
        foreach (\App\ExtraAnswerValue::valuesForSurvey($survey) as $extraQuestion) {
            $value = $request['extraQuestion_' . $extraQuestion->id()];
            if ($value != null) {
                array_push($constraints, (object)[
                    'id' => $extraQuestion->id(),
                    'value' => $value
                ]);
            }
        }

        return $constraints;
    }

    /**
    * Returns the month name for the given date
    */
    private function getMonthName($lang, $date)
    {
        switch ($date->month) {
            case 1:
                return Lang::get('date.january', [], $lang);
            case 2:
                return Lang::get('date.february', [], $lang);
            case 3:
                return Lang::get('date.march', [], $lang);
            case 4:
                return Lang::get('date.april', [], $lang);
            case 5:
                return Lang::get('date.may', [], $lang);
            case 6:
                return Lang::get('date.june', [], $lang);
            case 7:
                return Lang::get('date.july', [], $lang);
            case 8:
                return Lang::get('date.august', [], $lang);
            case 9:
                return Lang::get('date.september', [], $lang);
            case 10:
                return Lang::get('date.october', [], $lang);
            case 11:
                return Lang::get('date.november', [], $lang);
            case 12:
                return Lang::get('date.december', [], $lang);
            default:
                return '';
        }
    }

    /**
    * Generates the PDF for the report
    **/
    private function generateReportPDF($reportData)
    {
        ini_set('memory_limit', '256M');
        
        $survey = $reportData->survey;

        $html = $reportData->html;
        $isGroupReport = $reportData->isGroupReport;
        $recipientId = $reportData->recipientId;

        $reportLang = $reportData->lang;
        $this->setLanguageFromName($survey, $reportLang);

        $insertBackcoverPage = $reportData->insertBackcoverPage;
        $userReport = $reportData->userReport;
        $inlineReport = $reportData->inlineReport;

        $introPage = $reportData->introPage;
        $mainTitle = $reportData->mainTitle;
        $footer = $reportData->footer;

        $numInvitedText = $reportData->invitedText;
        $numAnsweredText = $reportData->answeredText;

        $css = ReportController::getIncludeContents('css/report.css');
        $bootstrap = ReportController::getIncludeContents('css/bootstrap.min.css');

        $mpdf = new \mPDF("utf-8");
        $mpdf->img_dpi = 200; //Increase quality of the images
        $mpdf->SetImportUse();
        $mpdf->setAutoTopMargin = 'stretch';

        //Get the logo, front cover and back cover
        if ($survey->type == \App\SurveyTypes::Individual) {
            $mpdf->lynxLogo = file_get_contents('images/report/360.png');
            $mpdf->frontCover = file_get_contents('images/report/front_360.png');
            $backCover = 'images/report/back_360.png';
        } else if ($survey->type == \App\SurveyTypes::Group) {
            $mpdf->lynxLogo = file_get_contents('images/report/lmtt.png');
            $mpdf->frontCover = file_get_contents('images/report/front_lmtt.png');
            $backCover = 'images/report/back_lmtt.png';
        } else if ($survey->type == \App\SurveyTypes::Progress) {
            $mpdf->lynxLogo = file_get_contents('images/report/progress.png');
            $mpdf->frontCover = file_get_contents('images/report/front_progress.png');
            $backCover = 'images/report/back_progress.png';
        } else if ($survey->type == \App\SurveyTypes::LTT) {
            $mpdf->lynxLogo = file_get_contents('images/report/ltt.png');
            $mpdf->frontCover = file_get_contents('images/report/front_ltt.png');
            $backCover = 'images/report/back_ltt.png';
        } else if ($survey->type == \App\SurveyTypes::Normal) {
            $mpdf->lynxLogo = file_get_contents('images/report/logo-report.png');
            $mpdf->frontCover = file_get_contents('images/report/front_survey.png');
            $backCover = 'images/report/back_survey.png';
        }

        $mpdf->WriteHTML($bootstrap, 1);
        $mpdf->WriteHTML($css, 1);

        $numInvited = 0;
        $numAnswered = 0;

        if (\App\SurveyTypes::isIndividualLike($survey->type)) {
            if ($isGroupReport) {
				$recipients = $survey->recipients()->get()->filter(function ($recipient) use (&$reportData) {
					if ($reportData->includeInGroupReport != null) {
						foreach ($reportData->includeInGroupReport as $candidateId) {
							if ($recipient->invitedById === $candidateId) {
								return true;
							}
						}

						return false;
					} else {
						return true;
					}
				});

				$numInvited = $recipients->count();
				$numAnswered = $recipients->filter(function ($recipient) {
					return $recipient->hasAnswered;
				})->count();
            } else {
                $toEvaluateRecipients = $survey->recipients()
                    ->where('invitedById', '=', $recipientId)
                    ->where('recipientId', '!=', $recipientId);

                $numInvited = $toEvaluateRecipients
                    ->count();

                $numAnswered = $toEvaluateRecipients
                    ->where('hasAnswered', '=', true)
                    ->count();
            }
        } else {
            $numInvited = $survey->recipients()->count();
            $numAnswered = $survey->recipients()->where('hasAnswered', '=', true)->count();
        }

        //Add the front page
        $mpdf->WriteHTML('
        <div class="frontPage" style="position: absolute; left:0; right: 0; top: 0; bottom: 0;">
            <img src="var:frontCover" style="width: 210mm; height: 297mm; margin: 0;" />
        </div>
        <div style="position: absolute; left:0; right: 0; top: 180px; bottom: 0;">
            <div class="mainTitle" style="text-align: center; font-family: calibri;">' . $mainTitle . '</div>
            <div class="coverPage" style="text-align: right; margin-top: 650px; margin-right: 105px">
                <h3 class="coverPage" style="font-family: calibri;">' . ucfirst($this->getMonthName($reportLang, $survey->endDate)) . ' ' . $survey->endDate->format('Y') . '</h3>
                <span style="font-family: calibri; font-size: 15px">
                    ' . $numInvitedText .  ': ' . $numInvited . '
                    <br>
                    ' . $numAnsweredText .  ': ' .  $numAnswered . '
                </span>
            </div>
        </div>
        ', 2);

        //Header
        $userLogo = 'images/logos/' . $survey->owner->name . '_logo.png';
        if (file_exists($userLogo)) {
            $mpdf->customerLogo = file_get_contents($userLogo);
            $mpdf->SetHTMLHeader('
            <table width="100%">
                <tr>
                    <td align="left" width="50%"><img width="20%" style="margin-bottom: 20px; max-height: 50px;" src="var:customerLogo"/></td>
                    <td align="right" width="50%"><img width="20%" style="margin-bottom: 20px;" src="var:lynxLogo"/></td>
                </tr>
            </table>
            ');
        } else {
            $mpdf->SetHTMLHeader('<img width="20%" style="margin-bottom: 20px;" src="var:lynxLogo"/>');
        }

        //Footer
        $mpdf->SetHTMLFooter('
        <table width="100%" style="vertical-align: bottom; font-family: calibri;">
            <tr>
                <td align="center" style="font-size: 6pt;">' . $footer . '</td>
                <td align="right" style="font-size: 6pt;">{PAGENO}</td>
            </tr>
        </table>
        ');

        //Add content
        $mpdf->AddPageByArray(['resetpagenum' => 1]);

        //Add intro page
        if ($introPage != "") {
            $parsedown = new \Parsedown();
            $introPage = $parsedown->text('<div class="description">' . $introPage . '</div>');
            $mpdf->WriteHTML($introPage, 2);

            if ($survey->type != \App\SurveyTypes::Progress) {
                $mpdf->AddPage();
            }
        } else {
            //Remove this if intro page is empty. If not removed, this will show an empty page.
            $html = str_replace('<div id="firstPage" class="diagrampage pageBreak"></div>', '', $html);
            $html = str_replace('<div id="firstPage" class="diagrampage"></div>', '', $html);
            $html = str_replace('<div class="diagrampage pageBreak" id="firstPage"></div>', '', $html);
            $html = str_replace('<div class="diagrampage" id="firstPage"></div>', '', $html);
        }

        $mpdf->WriteHTML($html, 2);

        //Add the back page so backcover is back when printing
        if ($mpdf->docPageNumTotal() % 2 != 0 && $insertBackcoverPage) {
            $mpdf->SetHTMLHeader('');
            $mpdf->SetHTMLFooter('');
            $mpdf->AddPage();
        }

        $mpdf->AddPage();
        $mpdf->Image($backCover, 0, 0, 210, 297, 'png', '', true, false);

        $date = \Carbon\Carbon::now(Survey::TIMEZONE)->format('Y-m-d H_m_s');
        $fileName = $survey->name . ' ' . $date . '.pdf';

        if ($inlineReport) {
            //Displays in inline instead of saving on the server
            $mpdf->Output($fileName, 'I');
            exit;
        } else {
            //Save to file & redirect
            $reportDir = $this->reportPath();

            if (!file_exists($reportDir)) {
                mkdir($reportDir);
            }

			$fileName = str_replace('/', '_', $fileName);
			$fileName = str_replace('\\', '_', $fileName);

            $mpdf->Output($this->reportPath($fileName) , 'F');

            $reportFile = new \App\Models\SurveyReportFile;
            $reportFile->fileName = $fileName;
            $survey->reports()->save($reportFile);
            return redirect(action('ReportController@viewReport', $reportFile->id));
        }
    }

    /**
     * Creates the pdf
     */
    public function createPDF(Request $request)
    {
        $survey = \App\Models\Survey::find($request->surveyId);

        if ($survey == null) {
            return redirect(action('SurveyController@index'));
        }

        if (!$this->canEditSurvey($survey)) {
            return redirect(action('SurveyController@index'));
        }

		$inlineReport = $request->inlineReport != null && $request->inlineReport == true;
		if ($request->preview != null && $request->preview == true) {
			$inlineReport = true;
		}

		$includeInGroupReport = null;
		if ($request->includeInGroupReport != null) {
			$includeInGroupReportIds = explode(',', $request->includeInGroupReport);
			$includeInGroupReport = [];

			foreach ($includeInGroupReportIds as $recipientId) {
				array_push($includeInGroupReport, intval($recipientId));
			}
		}

        $reportData = (object)[
            'survey' => $survey,
            'html' => $request->htmlContent,
            'isGroupReport' => $request->isGroupReport,
            'recipientId' => $request->recipientId,
            'lang' => $request->lang,
            'insertBackcoverPage' => $request->insertBackcoverPage != null && $request->insertBackcoverPage == "yes",
            'userReport' => $request->userReport != null && $request->userReport == "true",
            'inlineReport' => $inlineReport,
            'introPage' => $request->introPage,
            'mainTitle' => $request->mainTitle,
            'footer' => $request->footer,
            'invitedText' => $request->invitedText,
            'answeredText' => $request->answeredText,
			'includeInGroupReport' => $includeInGroupReport
        ];

        return $this->generateReportPDF($reportData);
    }

    //Returns the given report text either from user default or the given report template
    private static function getReportText($survey, $reportTemplate, $name)
    {
        return \App\Models\DefaultText::getReportText($survey, $survey->lang, $reportTemplate, $name);
    }

    /**
     * Creates the pdf for user reports
     */
    public function createUserPDF(Request $request)
    {
        $userReport = \App\Models\SurveyUserReport::find($request->userLink);

        if ($userReport == null) {
            return view('answer.notfound');
        }

        $survey = $userReport->survey;
        $reportTemplate = $survey->activeReportTemplate();

        //These reports uses predefined values
        $toEvaluate = $survey->candidates()
            ->where('recipientId', '=', $userReport->recipientId)
            ->first();

        $reportParserData = \App\EmailContentParser::createReportParserData($survey, $toEvaluate);

        $introPage = \App\EmailContentParser::parse(
            $this->getReportText($survey, $reportTemplate, 'defaultIntroPageReportText')->text,
            $reportParserData,
            true);

        $numAnsweredText = \App\EmailContentParser::parse(
            $this->getReportText($survey, $reportTemplate, 'defaultAnsweredReportText')->text,
            $reportParserData,
            true);

        $numInvitedText = \App\EmailContentParser::parse(
            $this->getReportText($survey, $reportTemplate, 'defaultInvitedReportText')->text,
            $reportParserData,
            true);

        $mainTitle = \App\EmailContentParser::parse(
            $this->getReportText($survey, $reportTemplate, 'defaultMainTitleReportText')->text,
            $reportParserData,
            true);

        $parserData['mainTitle'] = $mainTitle;

        $footer = \App\EmailContentParser::parse(
            $this->getReportText($survey, $reportTemplate, 'defaultFooterReportText')->text,
            $reportParserData,
            true);

        $reportData = (object)[
            'survey' => $survey,
            'html' => $request->htmlContent,
            'isGroupReport' => false,
            'recipientId' => $userReport->recipientId,
            'lang' => $survey->lang,
            'insertBackcoverPage' => true,
            'userReport' => true,
            'inlineReport' => true,
            'introPage' => $introPage,
            'mainTitle' => $mainTitle,
            'footer' => $footer,
            'invitedText' => $numInvitedText,
            'answeredText' => $numAnsweredText,
        ];

        return $this->generateReportPDF($reportData);
    }

    /**
    * Returns the comparison data for the given survey
    */
    private function getComparisonData($survey, $toEvaluate)
    {
        $comparisonData = null;
        $compareAgainstSurvey = $survey->compareAgainstSurvey;
        if ($compareAgainstSurvey != null) {
            if ($toEvaluate != null) {
                $compareToEvaluate = $compareAgainstSurvey->candidates()
                    ->where('recipientId', '=', $toEvaluate->recipientId)
                    ->first();
            } else {
                $compareToEvaluate = null;
            }

            $comparisonData = \App\SurveyReportProgress::create(
                $compareAgainstSurvey,
                $compareToEvaluate);

            $comparisonData->survey = $compareAgainstSurvey;
        }

        return $comparisonData;
    }

    /**
     * Shows a preview of the report before generating it
     */
    public function showReport(Request $request, $id)
    {
        $survey = Survey::findOrFail($id);

        if ($survey == null) {
            return redirect(action('SurveyController@index'));
        }

        $lang = $this->setLanguage($survey, $request);
        $inlineReport = $request->inlineReport ?: false;

        if (\App\SurveyTypes::isGroupLike($survey->type)) {
            $answerData = \App\SurveyReportGroup::create($survey);

            return view('survey.report')
                ->with(array_merge(
                    [
                        'inlineReport' => $inlineReport,
                        'userReportView' => false,
                        'lang' => $lang,
                        'survey' => $survey,
                        'toEvaluate' => null,
                        'reportTemplate' => $survey->activeReportTemplate(),
                    ],
                    (array)$answerData));
        } else if ($survey->type == \App\SurveyTypes::Individual) {
            $toEvaluate = $survey->candidates()
                ->where('recipientId', '=', $request->recipientId)
                ->first();

            if ($toEvaluate != null && !$toEvaluate->hasAnswered()) {
                $toEvaluate = null;
            }

			$includeInGroupReport = null;
			if ($request->includeInGroupReport != null && $request->includeInGroupReport != "") {
				$includeInGroupReport = [];
				foreach (explode(',', $request->includeInGroupReport) as $candidateId) {
					array_push($includeInGroupReport, intval($candidateId));
				}
			}

            $answerData = \App\SurveyReport360::create($survey, $toEvaluate, $includeInGroupReport);

            return view('survey.report')
                ->with(array_merge(
                    [
                        'inlineReport' => $inlineReport,
                        'userReportView' => false,
                        'lang' => $lang,
                        'survey' => $survey,
                        'toEvaluate' => $toEvaluate,
						'includeInGroupReport' => $includeInGroupReport,
                        'reportTemplate' => $survey->activeReportTemplate(),
                    ],
                    (array)$answerData));
        } else if ($survey->type == \App\SurveyTypes::Progress) {
            $toEvaluate = $survey->candidates()
                ->where('recipientId', '=', $request->recipientId)
                ->first();

            if ($toEvaluate != null && !$toEvaluate->hasAnswered()) {
                $toEvaluate = null;
            }

            $answerData = \App\SurveyReportProgress::create($survey, $toEvaluate);
            $comparisonData = $this->getComparisonData($survey, $toEvaluate);

            return view('survey.report')
                ->with(array_merge(
                    [
                        'inlineReport' => $inlineReport,
                        'userReportView' => false,
                        'lang' => $lang,
                        'survey' => $survey,
                        'toEvaluate' => $toEvaluate,
                        'reportTemplate' => $survey->activeReportTemplate(),
                        'comparisonData' => $comparisonData
                    ],
                    (array)$answerData));
        } else if ($survey->type == \App\SurveyTypes::Normal) {
            $answerData = \App\SurveyReportNormal::create(
                $survey,
                $this->extractConstraints($request, $survey));

            return view('survey.report')
                ->with(array_merge(
                    [
                        'inlineReport' => $inlineReport,
                        'userReportView' => false,
                        'lang' => $lang,
                        'survey' => $survey,
                        'toEvaluate' => null,
                        'reportTemplate' => null
                    ],
                    (array)$answerData));
        }
    }

    /**
    * Shows a user report (report that has been designed before hand and can be viewed via a link)
    */
    public function showUserReport(Request $request)
    {
        $userReport = \App\Models\SurveyUserReport::find($request->link);

        if ($userReport == null) {
            return view('answer.notfound');
        }

        $survey = $userReport->survey;
        $reportTemplate = $survey->activeReportTemplate();
        $lang = $this->setLanguageFromName($survey, $survey->lang);

        $toEvaluate = $survey->candidates()
            ->where('recipientId', '=', $userReport->recipientId)
            ->first();

        $answerData = \App\SurveyReportProgress::create($survey, $toEvaluate);
        $comparisonData = $this->getComparisonData($survey, $toEvaluate);

        return view('survey.report')
            ->with(array_merge(
                [
                    'inlineReport' => true,
                    'userReportView' => true,
                    'lang' => $lang,
                    'survey' => $survey,
                    'reportTemplate' => $reportTemplate,
                    'userLink' => $request->link,
                    'recipientId' => $toEvaluate->recipientId,
                    'toEvaluate' => $toEvaluate,
                    'comparisonData' => $comparisonData
                ],
                (array)$answerData));
    }

    /**
     * AJAX endpoint for retrieving a survey's shared reports.
     * 
     * @param   Illuminate\Http\Request $request
     * @param   integer                 $surveyId
     * @return  App\Http\JsonHalResponse
     */
    public function fetchReportShares(Request $request, $surveyId)
    {
        $this->validate($request, [
            'recipient_id'   => 'required|integer|exists:recipients,id',
        ]);

        $survey = Survey::findOrFail($surveyId);

        if ($survey->owner->isAn(User::ADMIN) || $survey->owner->isAn(User::SUPERADMIN)) {
            $company = $survey->owner;
        } else {
            $company = $survey->owner->company;
        }

        $shared = SurveyCandidateSharedReport::where([
            'surveyId'      => $survey->id,
            'recipientId'   => $request->recipient_id
        ])->with('user')->get();
        $sharedIds = $shared->map(function($item) {
            return $item->userId;
        })->toArray();

        $users = User::inTheCompany($company)
                    ->whereNotIn('id', $sharedIds)
                    ->whereIn('accessLevel', [0, 1, 2])
                    ->orderBy('name', 'ASC')
                    ->get();

        $shared = $shared->map(function($item) {
            return [
                'id'    => $item->user->id,
                'name'  => $item->user->name,
            ];
        });
        $users = $users->map(function($user) {
            return [
                'id'    => $user->id,
                'name'  => $user->name,
            ];
        });

        return response()->jsonHal([
            'users'     => $users,
            'shared'    => $shared
        ]);
    }

    /**
     * AJAX endpoint for saving a survey's shared reports.
     * 
     * @param   Illuminate\Http\Request $request
     * @param   integer                 $surveyId
     * @return  App\Http\JsonHalResponse
     */
    public function saveReportShares(Request $request, $surveyId)
    {
        $survey = Survey::findOrFail($surveyId);

        $this->validate($request, [
            'recipient_id'  => 'required|integer|exists:recipients,id',
            'shared'        => 'array',
            'shared.*'      => 'required|integer|exists:users,id'
        ]);

        $shared = $request->get('shared', []);

        $existing = SurveyCandidateSharedReport::where([
                'surveyId'      => $survey->id,
                'recipientId'   => $request->recipient_id
            ])
            ->get()
            ->map(function ($item) {
                return $item->userId;
            })
            ->toArray();

        $toAdd = array_diff($shared, $existing);
        $toRemove = array_diff($existing, $shared);
        
        foreach ($toAdd as $userId) {
            $scsr = new SurveyCandidateSharedReport;
            $scsr->surveyId = $survey->id;
            $scsr->recipientId = $request->recipient_id;
            $scsr->userId = $userId;
            $scsr->save();
        }

        foreach ($toRemove as $userId) {
            SurveyCandidateSharedReport::where([
                'surveyId'      => $survey->id,
                'recipientId'   => $request->recipient_id,
                'userId'        => $userId
            ])->delete();
        }

        return response('', 200);
    }

}
