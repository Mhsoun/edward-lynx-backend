<?php
    $maxNumRoles = 0;
    foreach ($categoriesByRole as $category) {
        $maxNumRoles = max($maxNumRoles, count($category->roles));
    }
?>

@foreach($categoriesByRole as $category)
    <script type="text/javascript">
        chartsToDraw.push(drawParticipantType);
        function drawParticipantType() {
            var dataPoints = [];
            dataPoints.push(['Title', 'Procentage', { role: 'style' }]);

            @foreach ($category->roles as $role)
                dataPoints.push([
                    {!! json_encode($role->name) !!},
                    {{ round($role->average, 2) }},
                    {{ $role->id == $selfRoleId ? 'selfColor' : 'orangeColor' }}
                ]);
            @endforeach

            var data = google.visualization.arrayToDataTable(dataPoints);
            var view = new google.visualization.DataView(data);

            view.setColumns([0, 1, {
                calc: function(dataTable, element) {
                    return Math.round(dataTable.getValue(element, 1) * 100) + "";
                },
                sourceColumn: 1,
                type: 'string',
                role: 'annotation'
            }, 2]);

            var chartWidth = 700;

            var options = {
                width: 700,
                height: {{ $maxNumRoles }} * (barWidth * 2 + 10) + 30,
                bar: { groupWidth: barWidth },
                legend: { position: 'left' },
                chartArea: { width: '60%', height: "70%", left: 220 },
                hAxis: {
                    title: '',
                    minValue: 0,
                    maxValue: 1,
                    format: "percent",
                    textStyle: diagramTextStyle,
                    ticks: getScaleWithThick7Line(chartWidth * 0.6, 2),
                    gridlines: axisLineStyle
                },
                vAxis: {
                    title: '',
                    textStyle: diagramTextStyle
                },
                fontSize: defaultFontSize,
                fontName: defaultFontName,
                annotations: {
                    alwaysOutside: true
                }
            };

            var chartDiv = document.getElementById('breakdown_{!! $category->id !!}');
            var chart = new google.visualization.BarChart(chartDiv);

            // Wait for the chart to finish drawing before calling the getIm geURI() method.
            google.visualization.events.addListener(chart, 'ready', function () {
                setImage(chartDiv, chart);
            });

            chart.draw(view, options);
        }
    </script>
@endforeach

<?php
    $breakdownPages = [];
    $breakdownPage = [];
    $breakdownPageCount = 0;

    $chartsPerPage = 6;

    if (count($rolesByCategory) <= 4) {
        $chartsPerPage = 8;
    }

    foreach ($categoriesByRole as $category) {
        $maxCount = $chartsPerPage;

        if ($breakdownPageCount + 1 > $maxCount) {
            array_push($breakdownPages, $breakdownPage);
            $breakdownPage = [];
            $breakdownPageCount = 0;
        }

        array_push($breakdownPage, $category);
        $breakdownPageCount++;
    }

    array_push($breakdownPages, $breakdownPage);
?>

@section('diagramContent')
    <div class="row twoColumnDiagram">
        <?php $i = 0; ?>
        @foreach ($breakdownPages as $page)
            <div class="pageBreak">
                @foreach ($page as $category)
                    @if ($i % 2 === 0)
                        <div class="left" style="clear: left;">
                            <h4>{{ $category->name }}</h4>
                            <div id="{!! 'breakdown_' . $category->id !!}"></div>
                        </div>
                    @else
                        <div class="right">
                            <h4>{{ $category->name }}</h4>
                            <div id="{!! 'breakdown_' . $category->id !!}"></div>
                        </div>
                    @endif
                    <?php $i++ ?>
                @endforeach
            </div>
        @endforeach
    </div>
@overwrite

@include('survey.reportpages.diagram', [
    'diagramName' => 'breakdown',
    'includeTitle' => Lang::get('report.breakdown'),
    'reportText' => getReportText($survey, 'defaultBreakdownReportText', $reportTemplate),
    'pageBreak' => false,
    'isPage' => true
])
