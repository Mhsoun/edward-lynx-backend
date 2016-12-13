<?php
    $i = 0;
    $highestLowestQuestions = [];
    $highestLowestRoles = $questionsByRole;

    //Extract the highest & lowest
    $highestLowestRoleQuestions = [];
    foreach ($highestLowestRoles as $role) {
        $newRole = (object)[
            'id' => $role->id,
            'name' => $role->name,
            'toEvaluate' => $role->toEvaluate,
            // 'lowest' => extractQuestions(array_reverse($role->questions), true),
            // 'highest' => extractQuestions($role->questions, true),
            'lowest' => extractQuestions2($role->questions, false, true),
            'highest' => extractQuestions2($role->questions, true, true),
        ];

        //Sort first by value, than by category
        usort($newRole->lowest, function($x, $y) {
            if ($x->average > $y->average) {
                return 1;
            } else if ($x->average < $y->average) {
                return -1;
            } else {
                return $x->categoryId - $y->categoryId;
            }
        });

        usort($newRole->highest, function($x, $y) {
            if ($x->average > $y->average) {
                return -1;
            } else if ($x->average < $y->average) {
                return 1;
            } else {
                return $x->categoryId - $y->categoryId;
            }
        });

        array_push($highestLowestRoleQuestions, $newRole);
    }
?>

<?php
    $highestAndLowestSamePage = count($highestLowestRoles) <= 2;
?>

@section('diagramContent')
    <div class="{{ $highestAndLowestSamePage ? 'pageBreak' : '' }}">
        <!-- Highest -->
        @section('diagramContent')
            @include('survey.reportpages.questionsForRole', [
                'roles' => $highestLowestRoleQuestions,
                'surveyParserData' => $surveyParserData,
                'getQuestions' => function ($role) {
                    return $role->highest;
                 }])
        @overwrite

        @include('survey.reportpages.diagram', [
            'diagramName' => 'highest',
            'titleText' => getReportText($survey, 'defaultGroupHighestReportText', $reportTemplate)->subject,
            'bodyText' => getReportText($survey, 'defaultGroupHighestReportText', $reportTemplate)->message,
            'pageBreak' => !$highestAndLowestSamePage,
            'noIncludeBox' => true
        ])

        <!-- Lowest -->
        @section('diagramContent')
            @include('survey.reportpages.questionsForRole', [
                'roles' => $highestLowestRoleQuestions,
                'surveyParserData' => $surveyParserData,
                'getQuestions' => function ($role) {
                    return $role->lowest;
                 }])
        @overwrite

        @include('survey.reportpages.diagram', [
            'diagramName' => 'lowest',
            'titleText' => getReportText($survey, 'defaultGroupLowestReportText', $reportTemplate)->subject,
            'bodyText' => getReportText($survey, 'defaultGroupLowestReportText', $reportTemplate)->message,
            'pageBreak' => !$highestAndLowestSamePage,
            'noIncludeBox' => true
        ])
    </div>
@overwrite

@include('survey.reportpages.diagram', [
    'diagramName' => 'highestLowest',
    'includeTitle' => Lang::get('report.highestLowestInclude'),
    'pageBreak' => false,
    'isPage' => true,
    'hasTitleAndText' => false
])
