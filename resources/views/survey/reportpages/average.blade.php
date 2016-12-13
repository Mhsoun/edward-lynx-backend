<?php
    $averageCategories = $categories;

    if (\App\SurveyTypes::isIndividualLike($survey->type)) {
        $averageCategories = $otherCategories;
    }
?>

<script type="text/javascript">
    @if (count($averageCategories) > 0)
        chartsToDraw.push(drawAverageValues);
    @endif

    function drawAverageValues() {
        var dataPoints = [["Title", "Procentage", { role: "style" }]];

        <?php $i = 0; ?>
        @foreach ($averageCategories as $category)
            dataPoints.push([
                "{!! $category->name !!}",
                {!! round($category->average, 2) !!},
                averageValueColor
            ]);
            <?php $i++; ?>
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

        var leftOffset = calculateTextSize(longestCategoryNameText, 35);
        var areaWidth = 700;
        var chartWidth = 100 + leftOffset + areaWidth;

        var options = {
            title: "",
            titleTextStyle: { fontSize: 25, bold: true },
            height: {{ count($averageCategories) }} * (barWidth * 2.2) + 100,
            width: chartWidth,
            chartArea: { width: ((areaWidth / chartWidth) * 100) + '%', height: '70%', left: leftOffset + 20 },
            bar: { groupWidth: barWidth },
            legend: { position: "left" },
            hAxis: {
                title: '',
                minValue: 0,
                maxValue: 1,
                format: "percent",
                ticks: getScaleWithThick7Line(areaWidth, 2),
                gridlines: axisLineStyle,
            },
            vAxis: {
                textStyle: {
                    fontSize: 35
                }
            },
            fontSize: 25,
            fontName: defaultFontName,
            annotations: {
                alwaysOutside: true,
            }
        };

        var chartDiv = document.getElementById('barchart_average');
        var chart = new google.visualization.BarChart(chartDiv);

        function makeSVG(tag, attrs) {
            var el = document.createElementNS('http://www.w3.org/2000/svg', tag);

            for (var k in attrs) {
                el.setAttribute(k, attrs[k]);
            }

            return el;
        }

        google.visualization.events.addListener(chart, 'ready', function () {
            // //Add gradinent
            // var gradient = makeSVG('linearGradient', { id: "grad1", x1: "0%", y1: "0%", x2: "100%", y2: "0%" });
            // gradient.appendChild(makeSVG('stop', { offset: "0%", style: "stop-color: rgb(255,255,0); stop-opacity: 1" }));
            // gradient.appendChild(makeSVG('stop', { offset: "100%", style: "stop-color: rgb(255,0,0); stop-opacity: 1" }));
            // $(chartDiv).find("defs")[0].appendChild(gradient);
            //
            // var bars = $($($($(chartDiv).find("svg").find("g")[0]).find("g")[0]).find("g")[1]).find("rect");
            setImage(chartDiv, chart);
        });

        chart.draw(view, options);
        // chart.C.C.Gf[0].c[2].v = "#000";
        // chart.C.C.Gf[0].c[2].v = "url(#grad1)";
    }
</script>

@section('diagramContent')
    <div id="barchart_average" style=""></div>
@overwrite

@include('survey.reportpages.diagram', [
    'diagramName' => 'averageValue',
    'includeTitle' => Lang::get('report.averageValues'),
    'reportText' => getReportText($survey, 'defaultAverageReportText', $reportTemplate),
    'pageBreak' => true
])
