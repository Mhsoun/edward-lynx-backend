@include('survey.reportpages.shared')

<div id="firstPage" class="diagrampage"></div>

<?php
    $includeProgressAverage = includeDiagram($reportTemplate, \App\Models\DefaultText::AverageReportText);
    $includeProgressRadar = includeDiagram($reportTemplate, \App\Models\DefaultText::ProgressRadarReportText);
    $includeRatingsPerQuestion = includeDiagram($reportTemplate, \App\Models\DefaultText::RatingsPerQuestionReportText);
    $includeRatingsPerQuestionRoles = includeDiagram($reportTemplate, \App\Models\DefaultText::ProgressRatingsPerRoleReportText);

    $questionToRoles = \App\SurveyReportHelpers::fromRolesToQuestions($selfAndOtherRoles);

    //Merges the given roles data
    function mergeRolesData($dataSets, $questionOrders) {
        $rolesData = [];
        foreach ($dataSets as $dataSet) {
            foreach ($dataSet->data as $role) {
                $newRole = null;
                if (array_key_exists($role->id, $rolesData)) {
                    $newRole = $rolesData[$role->id];
                } else {
                    $newRole = clone $role;
                    $newRole->questions = [];
                    $rolesData[$role->id] = $newRole;
                }

                foreach ($role->questions as $question) {
                    $newQuestion = null;
                    if (array_key_exists($question->id, $newRole->questions)) {
                        $newQuestion = $newRole->questions[$question->id];
                    } else {
                        $newQuestion = clone $question;
                        $newQuestion->data = [];
                        unset($newQuestion->average);
                        unset($newQuestion->answers);
                        unset($newQuestion->naAnswers);
                        $newRole->questions[$question->id] = $newQuestion;
                    }

                    array_push($newQuestion->data, (object)[
                        'survey' => $dataSet->survey,
                        'answers' => $question->answers,
                        'naAnswers' => $question->naAnswers,
                        'average' => $question->average
                    ]);
                }
            }
        }

        foreach ($rolesData as $role) {
            \App\SurveyReportHelpers::sortQuestionsByOrder($role->questions, $questionOrders);
        }

        return \App\SurveyReportHelpers::sortByRoleId($rolesData, $dataSets[0]->survey->type);
    }
?>

@if (!$userReportView || ($userReportView && $includeProgressAverage))
    @include('survey.reportpages.progressAverage')
@endif

@if (!$userReportView || ($userReportView && $includeProgressRadar))
    @include('survey.reportpages.progressRadar', ['$questionToRoles' => $questionToRoles])
@endif

@if (!$userReportView || ($userReportView && $includeRatingsPerQuestion))
    @include('survey.reportpages.ratingsPerQuestion', ['selfAndOtherRoles' => $selfAndOtherRoles, 'questionOrders' => $questionOrders])
@endif

@if (!$userReportView || ($userReportView && $includeRatingsPerQuestionRoles))
    @include('survey.reportpages.ratingsPerQuestionRoles', ['questionsByRole' => $questionsByRole, 'questionOrders' => $questionOrders])
@endif

<script type="text/javascript">
    function recalculatePageBreaks() {
        var pages = $(".diagramPage");

        //First, remove all page breaks
        pages.each(function(i, page) {
            $(page).removeClass('pageBreak');
        });

        var firstPage = $("#firstPage");
        var averagePage = $("#averageValuePage");
        var radarPage = $("#radarDiagramPage");
        var questionsPage = $("#allQuestionsPage");
        var questionsRolesPage = $("#allQuestionsRolesPage");

        //Then calculate the page breaks
        var pageBreakOrders = [averagePage, radarPage];
        var diagramsPerPage = 2;
        var numDiagrams = 1;
        var numVisible = 0;
        var lastVisible = null;

        pageBreakOrders.forEach(function (page, i) {
            if (page.is(':visible')) {
                numDiagrams++;
                numVisible++;

                if (numDiagrams == diagramsPerPage) {
                    page.addClass('pageBreak');
                    numDiagrams = 0;
                }

                lastVisible = page;
            }
        });

        if (numVisible == 0) {
            firstPage.addClass('pageBreak');
        } else if (lastVisible != null && !lastVisible.hasClass('pageBreak')) {
            lastVisible.addClass('pageBreak');
        }

        if (questionsPage.is(":visible")) {
            questionsPage.addClass('pageBreak');
        }

        if (questionsRolesPage.is(":visible")) {
            questionsRolesPage.addClass('pageBreak');
        }
    }
</script>
