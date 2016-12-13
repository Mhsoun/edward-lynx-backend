<?php
    if ($isGroup) {
        $currentCategoriesByRole = $categoriesByRole;
    } else {
        $currentCategoriesByRole = $selfAndOthersCategories;
        foreach ($currentCategoriesByRole as $category) {
            $category->roles = \App\SurveyReportHelpers::sortByRoleId($category->roles, $survey->type);
        }
    }

    function getColorName($roleId, $selfRoleId, $managerRoleId) {
        if ($roleId == $selfRoleId) {
            return 'selfColor';
        } else if ($roleId == $managerRoleId) {
            return 'managerColor';
        } else {
            return 'othersColor';
        }
    }
?>

<script type="text/javascript">
    chartsToDraw.push(drawCompetencies);

    function drawCompetencies() {
        var dataPoints = [];

        dataPoints.push({!! json_encode(array_merge(['Title'], array_map(function ($role) {
            return $role->name;
        }, $currentCategoriesByRole[0]->roles))) !!});

        @foreach ($currentCategoriesByRole as $category)
            <?php
                $rolesData = array_map(function ($role) {
                    return $role->average;
                }, $category->roles);

                // $numNull = 0;
                // $newData = [];
                // foreach ($rolesData as $average) {
                //     if ($average != null) {
                //         array_push($newData, $average);
                //     } else {
                //         $numNull++;
                //     }
                // }
                //
                // for ($i = 0; $i < $numNull; $i++) {
                //     array_push($newData, null);
                // }
            ?>
            dataPoints.push({!! json_encode(array_merge([$category->name], $rolesData)) !!});
        @endforeach

        var data = google.visualization.arrayToDataTable(dataPoints);
        var view = new google.visualization.DataView(data);

        var numRoles = {{ count($currentCategoriesByRole[0]->roles) }};
        var viewColumns = [0];

        for (var i = 1; i <= numRoles; i++) {
            viewColumns.push(i);
            viewColumns.push((function(index) {
                return {
                    calc: function(dataTable, element) {
                        return Math.round(dataTable.getValue(element, index) * 100) + "";
                    },
                    sourceColumn: index,
                    type: 'string',
                    role: 'annotation'
                };
            })(i));
        }
        view.setColumns(viewColumns);

        var colors = [];
        var longestRoleName = "";
        @foreach ($currentCategoriesByRole[0]->roles as $role)
            colors.push(getRoleColor({{ $role->id }}));
            if ({{ strlen($role->name) }} > longestRoleName.length) {
                longestRoleName = {!! json_encode($role->name) !!};
            }
        @endforeach

        var areaWidth = 800;
        var leftOffset = 20 + calculateTextSize(longestCategoryNameText, firstPageTextStyle.fontSize, defaultFontName);
        var chartWidth = leftOffset
            + areaWidth
            + 100
            + calculateTextSize(longestRoleName, legendTextStyle.fontSize, defaultFontName);

        var options = {
            title: '',
            chartArea: { width: ((areaWidth / chartWidth) * 100) + '%', height: '80%', left: leftOffset },
            height: {{ count($categories) }} * ((barWidth) * numRoles + 50) + 120,
            bar: { groupWidth: barWidth * numRoles },
            width: chartWidth,
            hAxis: {
                title: '',
                minValue: 0,
                maxValue: 1,
                format: "percent",
                ticks: getScaleWithThick7Line(areaWidth, 2),
                gridlines: axisLineStyle
            },
            vAxis: {
                title: '',
                textStyle: firstPageTextStyle
            },
            legend: {
                textStyle: legendTextStyle
            },
            fontSize: defaultFontSize,
            fontName: defaultFontName,
            colors: colors,
            annotations: {
                alwaysOutside: true
            }
        };

        var chartDiv = document.getElementById('barchart_indexOverCompetencies');
        var chart = new google.visualization.BarChart(chartDiv);

        // Wait for the chart to finish drawing before calling the getIm geURI() method.
        google.visualization.events.addListener(chart, 'ready', function () {
            setImage(chartDiv, chart);
        });

        chart.draw(view, options);
    }
</script>

@section('diagramContent')
    <div id="barchart_indexOverCompetencies"></div>
@overwrite

@include('survey.reportpages.diagram', [
    'diagramName' => 'indexOverCompetencies',
    'includeTitle' => Lang::get('report.indexOverCompetencies'),
    'reportText' => getReportText($survey, 'defaultIndexOverCompetenciesReportText', $reportTemplate),
    'pageBreak' => true,
    'isPage' => true
])
