@include('survey.reportpages.shared')

<!-- Group summary -->
{{-- @if ($isGroupReport)
    @include('survey.reportpages.groupsummary', ['categories' => $categories])
@endif --}}

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
            'name' => 'survey.reportpages.highestLowestIndividual',
            'data' => ['questionsByRole' => $questionsByRole],
            'order' => reportOrderOrDefault($reportTemplate, \App\Models\ReportTemplate::HighestLowestPage, 3),
        ],
        (object)[
            'name' => 'survey.reportpages.blindspotsIndividual',
            'data' => ['blindSpots' => $blindSpots],
            'order' => reportOrderOrDefault($reportTemplate, \App\Models\ReportTemplate::BlindspotsPage, 4),
        ],
        (object)[
            'name' => 'survey.reportpages.breakdown',
            'data' => ['categoriesByRole' => $categoriesByRole],
            'order' => reportOrderOrDefault($reportTemplate, \App\Models\ReportTemplate::BreakdownPage, 5),
        ],
        (object)[
            'name' => 'survey.reportpages.detailedanswersummary',
            'data' => [
                'categories' => $categories,
                'categoryAnswerFrequency' => $categoryAnswerFrequency
            ],
            'order' => reportOrderOrDefault($reportTemplate, \App\Models\ReportTemplate::DetailedAnswerSummaryPage, 6),
        ],
        (object)[
            'name' => 'survey.reportpages.yesorno',
            'data' => ['yesOrNoQuestions' => $yesOrNoQuestions],
            'order' => reportOrderOrDefault($reportTemplate, \App\Models\ReportTemplate::YesOrNoPage, 7),
        ]
    ];

    usort($pages, function ($x, $y) {
        return $x->order - $y->order;
    });
?>

@foreach ($pages as $page)
    @include($page->name, $page->data)
@endforeach
