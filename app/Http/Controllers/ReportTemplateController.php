<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Auth;
use Lang;
use App\Models\DefaultText;

/**
* Represents a controller for report templates
*/
class ReportTemplateController extends Controller
{
    	/**
	* Indicates if the auth user can edit the given report template
	*/
	private function canEditTemplate($reportTemplate)
	{
		if (Auth::user()->isAdmin) {
            return true;
        } else {
            return $reportTemplate->ownerId == Auth::user()->id;
        }
	}

    /**
    * Returns the  given language
    */
    private function getLang($lang)
    {
        if (!\App\Languages::isValid($lang)) {
            $lang = \App\Languages::all()[0];
        }

        return $lang;
    }

    /**
    * Returns the given survey type
    */
    private function getSurveyType($surveyType)
    {
        if (!\App\SurveyTypes::isValidType($surveyType)) {
            $surveyType = 0;
        }

        return $surveyType;
    }

	/**
	 * Shows all the report templates
	 */
	public function index()
	{
        $reportTemplates = \App\Models\ReportTemplate::
            where('ownerId', '=', Auth::user()->id)
            ->get();

		return view('reportTemplate.index', compact('reportTemplates'));
	}

	/**
	* Returns the view for creating a report template
	*/
	public function create(Request $request)
	{
        $surveyType = $this->getSurveyType(intval($request->surveyType));

        if (!Auth::user()->isAdmin) {
            if (!\App\SurveyTypes::canCreate(Auth::user()->allowedSurveyTypes, $surveyType)) {
                return redirect(action('SurveyController@index'));
            }
        }

        $surveyId = $request->surveyId;

        $lang = $this->getLang($request->lang);
        return view('reportTemplate.create', compact('surveyType', 'lang', 'surveyId'));
	}

    /**
    * Returns the ids for the default texts
    */
    private function getDefaultTextIds($surveyType, $lang)
    {
        $surveyTexts = [];
        $reportTexts = [];

        foreach (\App\Models\DefaultText::defaultReportTextsFor(Auth::user(), $surveyType)->reportTexts as $defaultText) {
             $getText = $defaultText->getText;
             $text = $getText($surveyType, $lang);

             if (isset($text->subject)) {
                 array_push($reportTexts, $text->type);
             } else {
                 array_push($surveyTexts, $text->type);
             }
        }

        return (object)[
            'surveyTexts' => $surveyTexts,
            'reportTexts' => $reportTexts,
        ];
    }

	/**
	* Returns none diagram text
	*/
	private function getNoneDiagramText($request, $textId)
	{
		$text = $request['text_' . $textId];

		if ($text == null) {
			$text = "";
		}

		return (object)[
			'text' => $text
		];
	}

	/**
	* Returns the diagram ext
	*/
	private function getDiagramText($request, $textId)
	{
		$title = $request['diagram_' . $textId . '_title'];
		$text = $request['diagram_' . $textId . '_text'];
		$include = $request['diagram_' . $textId . '_include'] == "yes";

		if ($title == null) {
			$title = "";
		}

		if ($text == null) {
			$text = "";
		}

		return (object)[
			'title' => $title,
			'text' => $text,
			'include' => $include
		];
	}

    /**
    * Adds the given report text to the given template
    */
    private function addReportText($reportTemplate, $textId, $text)
    {
        $templateDiagram = new \App\Models\ReportTemplateDiagram;
        $templateDiagram->typeId = $textId;
        $templateDiagram->isDiagram = false;
        $templateDiagram->text = $text;
        $reportTemplate->diagrams()->save($templateDiagram);
    }

    /**
    * Adds the given diagram text to the given template
    */
    private function addDiagramText($reportTemplate, $textId, $title, $text, $include)
    {
        $templateDiagram = new \App\Models\ReportTemplateDiagram;
        $templateDiagram->typeId = $textId;
        $templateDiagram->isDiagram = true;
        $templateDiagram->title = $title;
        $templateDiagram->text = $text;
        $templateDiagram->includeDiagram = $include;
        $reportTemplate->diagrams()->save($templateDiagram);
    }

	/**
    * Stores the given report template
    */
	public function store(Request $request)
	{
        $this->validate($request, [
            'name' => 'required',
            'surveyType' => 'required|integer',
            'lang' => 'required',
        ]);

        $surveyType = $this->getSurveyType(intval($request->surveyType));
        $lang = $this->getLang($request->lang);

        $defaultTexts = $this->getDefaultTextIds($surveyType, $lang);
        $surveyTexts = $defaultTexts->surveyTexts;
        $reportTexts = $defaultTexts->reportTexts;

		$reportTemplate = new \App\Models\ReportTemplate;
        $reportTemplate->name = $request->name;
        $reportTemplate->surveyType = $surveyType;
        $reportTemplate->lang = $lang;
        $reportTemplate->ownerId = Auth::user()->id;
		$reportTemplate->save();

        //Page orders
        foreach ($reportTemplate->pages() as $page) {
            $newPageOrder = $request['pageOrder_' . $page->pageId];
            $dbPage = $reportTemplate->pageOrders()
                ->where('pageId', '=', $page->pageId)
                ->first();

            if ($dbPage != null && $newPageOrder != "" && $newPageOrder != null) {
                $dbPage->order = $newPageOrder;
                $dbPage->save();
            }
        }

		//Report texts
		foreach ($surveyTexts as $textId) {
			$diagramText = $this->getNoneDiagramText($request, $textId);
            $this->addReportText($reportTemplate, $textId, $diagramText->text);
		}

		//Diagram texts
		foreach ($reportTexts as $textId) {
			$diagramText = $this->getDiagramText($request, $textId);
            $this->addDiagramText(
                $reportTemplate,
                $textId,
                $diagramText->title,
                $diagramText->text,
                $diagramText->include);
		}

        $backUrl = action('SurveyController@index');
        if ($request->surveyId != null) {
            $survey = \App\Models\Survey::find($request->surveyId);

            if ($survey != null) {
                $backUrl = action('SurveyController@edit', ['id' => $survey->id, 'activeTab' => 'reportTemplate']);
                $survey->activeReportTemplateId = $reportTemplate->id;
                $survey->save();
            }
        }

        return redirect($backUrl);
	}

	/**
	* Returns the edit view for the given report template
	*/
	public function edit($id)
	{
		$reportTemplate = \App\Models\ReportTemplate::find($id);

		if ($reportTemplate == null || !$this->canEditTemplate($reportTemplate)) {
			return redirect(action('ReportTemplateController@index'));
		}

		return view('reportTemplate.edit', compact('reportTemplate'));
	}

	/**
	 * Update the given report template
	 */
	public function update(Request $request, $id)
	{
		$reportTemplate = \App\Models\ReportTemplate::find($id);

		if ($reportTemplate == null || !$this->canEditTemplate($reportTemplate)) {
			return redirect(action('ReportTemplateController@index'));
		}

        //Page orders
        foreach ($reportTemplate->pages() as $page) {
            $newPageOrder = $request['pageOrder_' . $page->pageId];
            $dbPage = $reportTemplate->pageOrders()
                ->where('pageId', '=', $page->pageId)
                ->first();

            if ($dbPage != null && $newPageOrder != "" && $newPageOrder != null) {
                $dbPage->order = $newPageOrder;
                $dbPage->save();
            }
        }

        //If the diagram does not exist, create it.
        $defaultTexts = $this->getDefaultTextIds(
            $reportTemplate->surveyType,
            $reportTemplate->lang);

		//Report texts
		foreach ($defaultTexts->surveyTexts as $textId) {
            if (!$reportTemplate->exists($textId)) {
                $this->addReportText($reportTemplate, $textId, '');
            }
		}

		//Diagram texts
		foreach ($defaultTexts->reportTexts as $textId) {
            if (!$reportTemplate->exists($textId)) {
                $this->addDiagramText(
                    $reportTemplate,
                    $textId,
                    '',
                    '',
                    false);
            }
		}

		foreach ($reportTemplate->diagrams as $diagram) {
			if ($diagram->isDiagram) {
				$diagramText = $this->getDiagramText($request, $diagram->typeId);
				$diagram->title = $diagramText->title;
				$diagram->text = $diagramText->text;
				$diagram->includeDiagram = $diagramText->include;
			} else {
				$diagram->text = $this->getNoneDiagramText($request, $diagram->typeId)->text;
			}

			$diagram->save();
		}

		return view('reportTemplate.edit', compact('reportTemplate'));
	}

	/**
	 * Deletes the given report template
	 */
	public function delete(Request $request, $id)
	{
		$reportTemplate = \App\Models\ReportTemplate::find($id);

		if ($reportTemplate == null || !$this->canEditTemplate($reportTemplate)) {
			return redirect(action('ReportTemplateController@index'));
		}

		$reportTemplate->delete();
		return redirect(action('ReportTemplateController@index'));
	}
}
