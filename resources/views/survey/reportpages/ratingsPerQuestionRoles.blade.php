@section('diagramContent')
    <?php
        //Splits the given roles into self, manager and others combined
        function splitRoles($questionsByRole, $selfRoleId, $managerRoleId, $othersRoleName) {
            $roles = [];

            $othersRoleId = -1;
            $selfRole = null;
            $managerRole = null;
            $otherRoles = [];

            foreach ($questionsByRole as $role) {
                if ($selfRoleId == $role->id) {
                    $selfRole = clone $role;
                } else if ($managerRoleId == $role->id) {
                    $managerRole = clone $role;
                } else {
                    array_push($otherRoles, $role);
                }
            }

            $roles = array_filter([
                \App\SurveyReportHelpers::mergeQuestionRoles($otherRoles, $othersRoleId, $othersRoleName),
                $managerRole,
                $selfRole
            ], function ($role) {
                return $role != null;
            });

            foreach ($roles as $role) {
                $role->questions = \App\SurveyReportHelpers::calculateQuestionsAverage($role->questions);
            }

            return $roles;
        }

        if ($comparisonData != null) {
            $dataSets = [
                (object)[
                    'survey' => $comparisonData->survey,
                    'data' => splitRoles($comparisonData->questionsByRole, $selfRoleId, $managerRoleId, $othersRoleName),
                ],
                (object)[
                    'survey' => $survey,
                    'data' => splitRoles($questionsByRole, $selfRoleId, $managerRoleId, $othersRoleName),
                ],
            ];
        } else {
            $dataSets = [
                (object)[
                    'survey' => $survey,
                    'data' => splitRoles($questionsByRole, $selfRoleId, $managerRoleId, $othersRoleName),
                ],
            ];
        }

        $roles = mergeRolesData($dataSets, $questionOrders);

        $wantedRoleColors = [
            $selfRoleId => (object)[
                'color' => '#AA80B4',
                'strokeColor' => '#AA80B4',
                'textColor' => '#AA80B4',
                'olderColor' => '#774c82',
                'olderStrokeColor' => '#774c82',
                'olderTextColor' => '#774c82',
            ],
            $managerRoleId => (object)[
                'color' => '#6b4190',
                'strokeColor' => '#6b4190',
                'textColor' => '#4b2e65',
                'olderColor' => '#37214a',
                'olderStrokeColor' => '#37214a',
                'olderTextColor' => '#37214a',
            ],
            -1 => (object)[
                'color' => '#f79646',
                'strokeColor' => '#f79646',
                'textColor' => '#ad6931',
                'olderColor' => '#ce6209',
                'olderStrokeColor' => '#ce6209',
                'olderTextColor' => '#ce6209',
            ]
        ];
    ?>

    @include('survey.reportpages.allQuestionsDiagram', [
        'diagramName' => 'allQuestionsRolesDiagram',
        'questionsByRoles' => $roles,
        'wantedRoleColors' => $wantedRoleColors])
@overwrite

@include('survey.reportpages.diagram', [
    'diagramName' => 'allQuestionsRoles',
    'includeTitle' => Lang::get('report.progressRatingsPerRole'),
    'reportText' => getReportText($survey, 'defaultProgressRatingsPerRoleReportText', $reportTemplate),
    'pageBreak' => false
])
