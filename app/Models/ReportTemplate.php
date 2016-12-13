<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Lang;

/**
* Represents a template for survey report
*/
class ReportTemplate extends Model
{
    /**
    * The database table used by the model
    */
    protected $table = 'report_templates';

    protected $fillable = [];
    public $timestamps = false;

    const IndexOverCompetenciesPage = 1;
    const RadarPage = 2;
    const CommentsPage = 3;
    const HighestLowestPage = 4;
    const BlindspotsPage = 5;
    const BreakdownPage = 6;
    const DetailedAnswerSummaryPage = 7;
    const YesOrNoPage = 8;
    const ResultsPerCategoryPage = 9;

    /**
	* Returns the diagrams in the report
	*/
	public function diagrams()
	{
		return $this->hasMany('\App\Models\ReportTemplateDiagram', 'reportTemplateId');
	}

    /**
	* Returns the page orders in the report
	*/
	public function pageOrders()
	{
		return $this->hasMany('\App\Models\ReportTemplatePageOrder', 'reportTemplateId');
	}

    /**
    * Indicates if a diagram of the given type exists
    */
    public function exists($typeId)
    {
        return $this->diagrams()->where('typeId', '=', $typeId)->count() > 0;
    }

    /**
    * Returns the individual page orders
    */
    public static function individualPageOrders()
    {
        return [
            (object)['name' => Lang::get('report.indexOverCompetencies'), 'pageId' => ReportTemplate::IndexOverCompetenciesPage, 'order' => 1],
            (object)['name' => Lang::get('report.radarDiagram'), 'pageId' => ReportTemplate::RadarPage, 'order' => 2],
            (object)['name' => Lang::get('report.comments'), 'pageId' => ReportTemplate::CommentsPage, 'order' => 3],
            (object)['name' => Lang::get('report.highestLowestInclude'), 'pageId' => ReportTemplate::HighestLowestPage, 'order' => 4],
            (object)['name' => Lang::get('report.blindspots'), 'pageId' => ReportTemplate::BlindspotsPage, 'order' => 5],
            (object)['name' => Lang::get('report.breakdown'), 'pageId' => ReportTemplate::BreakdownPage, 'order' => 6],
            (object)['name' => Lang::get('report.detailed'), 'pageId' => ReportTemplate::DetailedAnswerSummaryPage, 'order' => 7],
            (object)['name' => Lang::get('report.yesOrNo'), 'pageId' => ReportTemplate::YesOrNoPage, 'order' => 8],
        ];
    }

    /**
    * Returns the group page orders
    */
    public static function groupPageOrders()
    {
        return [
            (object)['name' => Lang::get('report.indexOverCompetencies'), 'pageId' => ReportTemplate::IndexOverCompetenciesPage, 'order' => 1],
            (object)['name' => Lang::get('report.radarDiagram'), 'pageId' => ReportTemplate::RadarPage, 'order' => 2],
            (object)['name' => Lang::get('report.comments'), 'pageId' => ReportTemplate::CommentsPage, 'order' => 3],
            (object)['name' => Lang::get('report.resultsPerCategory'), 'pageId' => ReportTemplate::ResultsPerCategoryPage, 'order' => 4],
            (object)['name' => Lang::get('report.highestLowestInclude'), 'pageId' => ReportTemplate::HighestLowestPage, 'order' => 5],
            (object)['name' => Lang::get('report.blindspots'), 'pageId' => ReportTemplate::BlindspotsPage, 'order' => 6],
            (object)['name' => Lang::get('report.detailed'), 'pageId' => ReportTemplate::DetailedAnswerSummaryPage, 'order' => 7],
        ];
    }

    /**
    * Creates a new order for the given page
    */
    private function createOrderFor($pageId)
    {
        $order = $this->pageOrders()->max('order') + 1;
        $pageOrder = new \App\Models\ReportTemplatePageOrder;
        $pageOrder->pageId = $pageId;
        $pageOrder->order = $order;
        $this->pageOrders()->save($pageOrder);
        return $order;
    }

    /**
    * Returns the order for the given page
    */
    public function orderFor($pageId)
    {
        $pageOrder = $this->pageOrders()
            ->where('pageId', '=', $pageId)
            ->first();

        if ($pageOrder != null) {
            return $pageOrder->order;
        }

        return $this->createOrderFor($pageId);
    }

    /**
    * Returns the page orders
    */
    public function pages()
    {
        $pages = [];

        if ($this->surveyType == \App\SurveyTypes::Individual) {
            $pages = ReportTemplate::individualPageOrders();
        } else if (\App\SurveyTypes::isGroupLike($this->surveyType)) {
            $pages = ReportTemplate::groupPageOrders();
        }

        $pages = array_map(function ($page) {
            $newPage = clone $page;
            $newPage->order = $this->orderFor($page->pageId);
            return $newPage;
        }, $pages);

        usort($pages, function ($x, $y) {
            return $x->order - $y->order;
        });

        return $pages;
    }
}
