@include('survey.reportpages.shared')

<!-- Response rate, average values -->
<div id="firstPage">
    @include('survey.reportpages.responseRate')
    @include('survey.reportpages.average', ['categories' => $categories])
</div>

<?php
    $pages = [
        (object)[
            'name' => 'survey.reportpages.indexovercompetencies',
            'data' => ['selfAndOthersCategories' => $selfAndOthersCategories],
            'order' => reportOrderOrDefault($reportTemplate, \App\Models\ReportTemplate::IndexOverCompetenciesPage, 0),
        ],
        (object)[
            'name' => 'survey.reportpages.roleRadar',
            'data' => ['selfAndOthersCategories' => $selfAndOthersCategories],
            'order' => reportOrderOrDefault($reportTemplate, \App\Models\ReportTemplate::RadarPage, 1),
        ],
        (object)[
            'name' => 'survey.reportpages.comments',
            'data' => ['comments' => $comments],
            'order' => reportOrderOrDefault($reportTemplate, \App\Models\ReportTemplate::CommentsPage, 2),
        ],
        (object)[
            'name' => 'survey.reportpages.resultsPerCategory',
            'data' => ['questionsByRole' => $questionsByRole],
            'order' => reportOrderOrDefault($reportTemplate, \App\Models\ReportTemplate::ResultsPerCategoryPage, 3),
        ],
        (object)[
            'name' => 'survey.reportpages.highestLowestGroup',
            'data' => ['questionsByRole' => $questionsByRole],
            'order' => reportOrderOrDefault($reportTemplate, \App\Models\ReportTemplate::HighestLowestPage, 4),
        ],
        (object)[
            'name' => 'survey.reportpages.blindspotsGroup',
            'data' => ['blindSpots' => $blindSpots],
            'order' => reportOrderOrDefault($reportTemplate, \App\Models\ReportTemplate::BlindspotsPage, 5),
        ],
        (object)[
            'name' => 'survey.reportpages.detailedanswersummary',
            'data' => [
                'categories' => $categories,
                'categoryAnswerFrequency' => $categoryAnswerFrequency
            ],
            'order' => reportOrderOrDefault($reportTemplate, \App\Models\ReportTemplate::DetailedAnswerSummaryPage, 6),
        ]
    ];

    usort($pages, function ($x, $y) {
        return $x->order - $y->order;
    });
?>

@foreach ($pages as $page)
    @include($page->name, $page->data)
@endforeach

<script type="text/javascript">
    function recalculatePageBreaks() {
        var categoryResults = $(".categoryResults");

        //Remove page breaks
        categoryResults.each(function(i, categoryResult) {
            $(categoryResult).removeClass('pageBreak');
        });

        var maxPageHeight = 800;
        var currentPageHeight = 50;

        var prevCategory = null;

        categoryResults.each(function(i, categoryResult) {
            categoryResult = $(categoryResult);

            if (currentPageHeight + categoryResult.height() > maxPageHeight) {
                if (prevCategory != null) {
                    prevCategory.addClass('pageBreak');
                }

                currentPageHeight = 0;
            }

            if (i == categoryResults.length - 1) {
                categoryResult.addClass('pageBreak');
            }

            currentPageHeight += categoryResult.height();
            prevCategory = categoryResult;
        });
    }
</script>
