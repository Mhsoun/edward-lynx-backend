<?php
    $radarData = [];
    $radarCategories = [];

    if ($isGroup) {
        $radarCategories = $categoriesByRole;
    } else {
        $radarCategories = $selfAndOthersCategories;
    }

    foreach ($radarCategories as $category) {
        $radarCategory = (object)[
            'name' => $category->name,
            'roles' => []
        ];

        foreach ($category->roles as $role) {
            array_push($radarCategory->roles, (object)[
                'id' => $role->id,
                'name' => $role->name,
                'average' => round($role->average * 100),
            ]);
        }

        array_push($radarData, $radarCategory);
    }
?>

@include('survey.reportpages.radarDiagram', [
    'diagramName' => 'radarDiagram',
    'includeTitle' => Lang::get('report.radarDiagram'),
    'reportText' => getReportText($survey, 'defaultRadarReportText', $reportTemplate),
    'pageBreak' => true,
    'radarData' => $radarData,
    'isPage' => true
])
