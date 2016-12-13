<?php
    $radarData = [];
    $isComparison = false;

    if ($comparisonData != null) {
        $dataSets = [
            (object)['survey' => $survey, 'questions' => $questionToRoles, 'older' => true],
            (object)[
                'survey' => $comparisonData->survey,
                'questions' => \App\SurveyReportHelpers::fromRolesToQuestions($comparisonData->selfAndOtherRoles),
                'older' => false
            ],
        ];

        $isComparison = true;
    } else {
        $dataSets = [
            (object)['survey' => $survey, 'questions' => $questionToRoles, 'older' => false],
        ];
    }

    $questionData = [];
    foreach ($dataSets as $data) {
        foreach ($data->questions as $question) {
            $newQuestion = null;
            if (array_key_exists($question->id, $questionData)) {
                $newQuestion = $questionData[$question->id];
            } else {
                $newQuestion = clone $question;
                $newQuestion->roles = [];
                $questionData[$question->id] = $newQuestion;
            }

            foreach ($question->roles as $role) {
                $newRole = clone $role;

                if ($isComparison) {
                    $newRole->name = $role->name . ' (' . $data->survey->endDate->format('d M Y') . ')';
                }

                $newRole->older = $data->older;

                array_push($newQuestion->roles, $newRole);
            }
        }
    }

    foreach ($questionData as $question) {
        $question->roles = \App\SurveyReportHelpers::sortByRoleId($question->roles, $survey->type);
    }

    \App\SurveyReportHelpers::sortQuestionsByOrder($questionData, $questionOrders);

    $i = 1;
    foreach ($questionData as $question) {
        $radarQuestion = (object)[
            'name' => $i . '',
            'roles' => array_map(function ($role) {
                return (object)[
                    'id' => $role->id,
                    'name' =>  $role->name,
                    'average' => $role->average * 100,
                    'darken' => $role->older
                ];
             }, $question->roles)
        ];

        array_push($radarData, $radarQuestion);
        $i++;
    }
?>

@include('survey.reportpages.radarDiagram', [
    'diagramName' => 'radarDiagram',
    'includeTitle' => Lang::get('report.radarDiagram'),
    'reportText' => getReportText($survey, 'defaultProgressRadarReportText', $reportTemplate),
    'pageBreak' => false,
    'radarData' => $radarData,
    'showAreasLegend' => false
])
