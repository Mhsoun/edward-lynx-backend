@section('diagramContent')
    <?php
        $wantedRoleColors = [
            $selfRoleId => (object)[
                'color' => '#AA80B4',
                'strokeColor' => '#AA80B4',
                'textColor' => '#AA80B4',
                'olderColor' => '#774c82',
                'olderStrokeColor' => '#774c82',
                'olderTextColor' => '#774c82',
            ],
            -1 => (object)[
                'color' => '#f79646',
                'strokeColor' => '#f79646',
                'textColor' => '#f79646',
                'olderColor' => '#ce6209',
                'olderStrokeColor' => '#ce6209',
                'olderTextColor' => '#ce6209',
            ]
        ];

        if ($comparisonData != null) {
            $dataSets = [
                (object)[
                    'survey' => $comparisonData->survey,
                    'data' => $comparisonData->selfAndOtherRoles,
                ],
                (object)[
                    'survey' => $survey,
                    'data' => $selfAndOtherRoles,
                ],
            ];
        } else {
            $dataSets = [
                (object)[
                    'survey' => $survey,
                    'data' => $selfAndOtherRoles,
                ],
            ];
        }

        $rolesData = mergeRolesData($dataSets, $questionOrders);
    ?>

    @include('survey.reportpages.allQuestionsDiagram', [
        'diagramName' => 'allQuestionsDiagram',
        'questionsByRoles' => $rolesData,
        'wantedRoleColors' => $wantedRoleColors
    ])
@overwrite

@include('survey.reportpages.diagram', [
    'diagramName' => 'allQuestions',
    'includeTitle' => Lang::get('report.allQuestions'),
    'reportText' => getReportText($survey, 'defaultRatingsPerQuestionReportText', $reportTemplate),
    'pageBreak' => true,
])
