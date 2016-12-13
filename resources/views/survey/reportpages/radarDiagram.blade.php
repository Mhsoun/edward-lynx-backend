<?php
    $radarDataForRole = [];

    foreach ($radarData as $radarCategory) {
        foreach ($radarCategory->roles as $role) {
            $roleData = null;

            if (array_key_exists($role->name, $radarDataForRole)) {
                $roleData = $radarDataForRole[$role->name];
            } else {
                $roleData = (object)[
                    'id' => $role->id,
                    'name' => $role->name,
                    'darken' => isset($role->darken) ? $role->darken : false,
                    'categories' => []
                ];

                $radarDataForRole[$role->name] = $roleData;
            }

            array_push($roleData->categories, (object)[
                'name' => $radarCategory->name,
                'average' => $role->average,
            ]);
        }
    }

    $legendAboveChart = false;
?>

<script type="text/javascript">
    @if (count($radarDataForRole) > 0)
        var radarDataPoints = [];
        var titles = [];
        var roleTitles = [];

        <?php $i = 1; ?>
        @foreach ($radarData as $category)
            titles.push({{ $i }});
            <?php $i++; ?>
        @endforeach

        @foreach ($radarDataForRole as $roleData)
            var color = getRoleColor({{ $roleData->id }}, {!! json_encode($roleData->darken) !!});

            radarDataPoints.push({
                label: {!! json_encode($roleData->name) !!},
                fillColor: "rgba(220, 220, 220, 0)",
                strokeColor: color,
                pointColor: color,
                pointStrokeColor: color,
                pointHighlightFill: color,
                pointHighlightStroke: color,
                data: {!! json_encode(array_map(function ($category) {
                    return $category->average;
                }, $roleData->categories)) !!}
            });

            roleTitles.push({
                name: {!! json_encode($roleData->name) !!},
                color: color
            });
        @endforeach

        var radarChartData = {
            labels:  titles,
            datasets: radarDataPoints
        };

        $(document).ready(function() {
            //Add the legend
            var radarLegend = $("#radarLegend_{{ $diagramName }}");

            roleTitles.forEach(function(roleTitle) {
                var legendItem = jQuery("<div>");

                legendItem.append(jQuery("<img width='15px' height='15px' />")
                    .css("margin-right", "3px")
                    .css("margin-left", "30px")
                    .attr("src", getImageSquare(roleTitle.color)));

                legendItem.append(jQuery("<span />")
                    .text(roleTitle.name));

                radarLegend.append(legendItem);
                radarLegend.append("<div style='color: white; font-size: 4px;'>S</div>");
            });

            var fontSize = 20;
            var radarDiagramElement = document.getElementById("radar_{{ $diagramName }}");

            //Add the graph
            new Chart(radarDiagramElement.getContext("2d")).Radar(radarChartData, {
                responsive: true,
                pointLabelFontSize: fontSize,
                pointLabelFontFamily: "Calibri",
                scaleFontSize: 16,
                scaleShowLabels: true,
                scaleOverride: true,
                scaleSteps: 5,
                scaleStepWidth: 20,
                scaleStartValue: 0,
                scaleLineWidth: 2,
                scaleLineColor: "#9E9E9E",
                angleLineColor: "#9E9E9E",
                angleLineWidth: 2,
                scaleFontFamily: "Calibri",
                pointDotStrokeWidth: 3,
                datasetStrokeWidth : 3,
                onAnimationComplete: function () {
                    var radarCanvas = radarDiagramElement .getContext("2d").canvas;

                    //Replace content of div with new image
                    var img = new Image();
                    img.value = radarCanvas.toDataURL("image/png");
                    setRadarImage(document.getElementById("radarCanvas_{{ $diagramName }}"), img);
                }
            });
        });
    @endif
</script>

@section('diagramContent')
    <?php
        if (!isset($showAreasLegend)) {
            $showAreasLegend = true;
        }
    ?>

    @if ($showAreasLegend)
        <h4>{{ Lang::get('report.areas') }}</h4>
        <ol>
            @foreach ($radarData as $category)
                <li>{{ $category->name }}</li>
            @endforeach
        </ol>
        <br>
        <br>
    @endif

    <table style="width: 100%; margin-left: 50px;">
        <tr>
            <td>
                <div id="radarCanvas_{{ $diagramName }}" style="width: 950px; height: 950px;">
                    <canvas width="100" height="100" id="radar_{{ $diagramName }}"></canvas>
                </div>
            </td>
            <td style="vertical-align: middle;" width="30%">
                <div id="radarLegend_{{ $diagramName }}" class="description"></div>
            </td>
        </tr>
    </table>
@overwrite

@include('survey.reportpages.diagram', [
    'diagramName' => $diagramName,
    'includeTitle' => $includeTitle,
    'reportText' => $reportText,
    'pageBreak' => $pageBreak
])
