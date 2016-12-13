<?php
    $averageData = [];
    $averageDataSets = [];
    $isComparison = false;

    if ($comparisonData != null) {
        $averageDataSets = [
            (object)['survey' => $comparisonData->survey, 'data' => $comparisonData->selfAndOthersAverage, 'older' => false],
            (object)['survey' => $survey, 'data' => $selfAndOthersAverage, 'older' => true],
        ];

        $isComparison = true;
    } else {
        $averageDataSets = [
            (object)['survey' => $survey, 'data' => $selfAndOthersAverage, 'older' => false],
        ];
    }

    foreach ($averageDataSets as $dataSet) {
        foreach ($dataSet->data as $role) {
            $roleData = null;

            if (array_key_exists($role->id, $averageData)) {
                $roleData = $averageData[$role->id];
            } else {
                $roleData = clone $role;
                $roleData->data = [];
                unset($roleData->average);
                $averageData[$role->id] = $roleData;
            }

            array_push($roleData->data, (object)[
                'name' => $isComparison ? $dataSet->survey->endDate->format('d M Y') : '',
                'value' => $role->average,
                'older' => $dataSet->older
            ]);
        }
    }

    $averageData = \App\SurveyReportHelpers::sortByRoleId($averageData, $survey->type);
?>

<script type="text/javascript">
    chartsToDraw.push(drawAverageValues);

    function drawAverageValues() {
        var dataPoints = [["Title", "Procentage", { role: "style" }]];

        @foreach ($averageData as $role)
            @foreach ($role->data as $point)
                dataPoints.push([
                     {!! json_encode($role->name . ($point->name != "" ? ' (' . $point->name . ')' : '')) !!},
                     {{ round($point->value, 2) }},
                     getRoleColor({{ $role->id }}, {!! json_encode($point->older) !!})
                ]);
            @endforeach
        @endforeach

        var data = google.visualization.arrayToDataTable(dataPoints);

        var view = new google.visualization.DataView(data);
        view.setColumns([0, 1, {
            calc: function(dataTable, element) {
                return Math.round(dataTable.getValue(element, 1) * 100) + "";
            },
            sourceColumn: 1,
            type: "string",
            role: "annotation",
        }, 2]);

        var options = {
            title: "",
            titleTextStyle: { fontSize: 25, bold: true },
            height: 500,
            chartArea: { width: '90%', height: '70%', left: 100 },
            bar: { groupWidth: barWidth * 3 },
            legend: { position: "left" },
            hAxis: {
                title: '',
            },
            vAxis: {
                textStyle: firstPageTextStyle,
                minValue: 0,
                maxValue: 1,
                format: "percent",
                ticks: getScaleWithThick7Line(600 * 0.5, 2),
                gridlines: axisLineStyle
            },
            fontSize: defaultFontSize,
            fontName: defaultFontName,
            annotations: {
                alwaysOutside: true
            }
        };

        var chartDiv = document.getElementById('averageDiagram');
        var chart = new google.visualization.ColumnChart(chartDiv);

        // Wait for the chart to finish drawing before calling the getIm geURI() method.
        google.visualization.events.addListener(chart, 'ready', function () {
            setImage(chartDiv, chart);
        });

        chart.draw(view, options);
    }
</script>

@section('diagramContent')
    <div id="averageDiagram" style=""></div>
@overwrite

@include('survey.reportpages.diagram', [
    'diagramName' => 'averageValue',
    'includeTitle' => Lang::get('report.averageValues'),
    'reportText' => getReportText($survey, 'defaultAverageReportText', $reportTemplate),
    'pageBreak' => false,
])
