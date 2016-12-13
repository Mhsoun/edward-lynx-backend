<script type="text/javascript">
    function drawGapDiagram(chartDivName, scale, selfTitle, selfValue, othersRoleId, othersTitle, othersValue) {
        chartsToDraw.push(drawChart);

        function drawChart() {
            drawGapDiagramInternal(
                document.getElementById(chartDivName),
                scale,
                selfTitle,
                selfValue,
                othersRoleId,
                othersTitle,
                othersValue);
        }
    }

    function drawGapDiagramInternal(chartElement, scale, selfTitle, selfValue, othersRoleId, othersTitle, othersValue) {
        var dataPoints = [
            ["Title", "Value", { role: "style" }, "Value", { role: "style" }],
            [othersTitle, othersValue, getRoleColor(othersRoleId), othersValue, "#000000"],
            [selfTitle, selfValue, getRoleColor({{ $selfRoleId }}), selfValue, "#000000"]
        ];

        var data = google.visualization.arrayToDataTable(dataPoints);
        var view = new google.visualization.DataView(data);
        view.setColumns([0, 1, {
            calc: 'stringify',
            sourceColumn: 1,
            type: 'string',
            role: 'annotation'
        }, 2, 3, 4]);

        var minValue = 0;
        var maxValue = 100;

        var scaleFontSize = defaultFontSize;

        var width = 650;
        var leftOffset = 5;

        var options = {
            title: '',
            chartArea: { height: '60%', width: (((width - leftOffset * 1.5) / width) * 100) + '%', left: leftOffset },
            height: 400,
            width: width,
            bar: { groupWidth: barWidth * 2.5 },
            hAxis: {
                title: '',
            },
            vAxis: {
                title: '',
                minValue: minValue,
                maxValue: maxValue,
                // ticks: scale,
                ticks: [],
                gridlines: axisLineStyle,
                textStyle: {
                    fontSize: scaleFontSize
                }
            },
            legend: {
                position: 'none'
            },
            fontSize: defaultFontSize,
            fontName: defaultFontName,
            annotations: {
                alwaysOutside: true
            },
            seriesType: 'bars',
            series: { 1: { type: 'line' } }
        };

        var chart = new google.visualization.ComboChart(chartElement);

        // Wait for the chart to finish drawing before calling the getIm geURI() method.
        google.visualization.events.addListener(chart, 'ready', function () {
            setImage(chartElement, chart);
        });

        chart.draw(view, options);
    }

    @foreach ($blindSpots as $role)
        <?php $i = 0; ?>
        @foreach ($role->overestimated as $question)
            drawGapDiagram(
                "barchart_blindspot_over_{{ $role->id }}_{{ $i }}",
                {!! json_encode(getAnswerValues($question->id)) !!},
                {!! json_encode($selfRoleName) !!},
                {{ $question->self }},
                {{ $role->id }},
                {!! json_encode($role->name) !!},
                {{ $question->others }});
            <?php $i++; ?>
        @endforeach

        <?php $i = 0; ?>
        @foreach ($role->underestimated as $question)
            drawGapDiagram(
                "barchart_blindspot_under_{{ $role->id }}_{{ $i }}",
                {!! json_encode(getAnswerValues($question->id)) !!},
                {!! json_encode($selfRoleName) !!},
                {{ $question->self }},
                {{ $role->id }},
                {!! json_encode($role->name) !!},
                {{ $question->others }});
            <?php $i++; ?>
        @endforeach
    @endforeach
</script>

@section('diagramContent')
    @foreach ($blindSpots as $role)
        <h3>{{ $role->name }}</h3>

        <!-- Overestimated -->
        @section('diagramContent')
            <?php $i = 0; ?>
            <div class="row twoColumnDiagram">
                @foreach ($role->overestimated as $question)
                    <?php
                        $divHeader = '';
                        if ($i % 2 === 0) {
                            $divHeader = 'class="description left" style="clear: left;"';
                        } else {
                            $divHeader = 'class="description right"';
                        }
                    ?>

                    <div {!! $divHeader !!}>
                        <h4>{{ $question->category }}</h4>
                        <i><b>{{ \App\EmailContentParser::parse($question->title, $surveyParserData, true, true) }}</b></i>
                        <div id="barchart_blindspot_over_{{ $role->id }}_{{ $i }}"></div>
                        <br>
                    </div>
                    <?php $i++; ?>
                @endforeach
            </div>

            @if (count($role->overestimated) == 0)
                <b>{{ Lang::get('report.noOverestimatedText') }}</b>
            @endif
        @overwrite

        @include('survey.reportpages.diagram', [
            'diagramName' => 'blinspotOver',
            'titleText' => getReportText($survey, 'defaultBlindspotsOverReportText', $reportTemplate)->subject,
            'bodyText' => getReportText($survey, 'defaultBlindspotsOverReportText', $reportTemplate)->message,
            'pageBreak' => count($role->underestimated) > 0,
            'noIncludeBox' => true,
            'titleLevel' => 'h4'
        ])

        <!-- Underestimated -->
        @section('diagramContent')
            <?php $i = 0; ?>
            <div class="row twoColumnDiagram">
                @foreach ($role->underestimated as $question)
                    <?php
                        $divHeader = '';
                        if ($i % 2 === 0) {
                            $divHeader = 'class="description left" style="clear: left;"';
                        } else {
                            $divHeader = 'class="description right"';
                        }
                    ?>

                    <div {!! $divHeader !!}>
                        <h4>{{ $question->category }}</h4>
                        <i><b>{{ \App\EmailContentParser::parse($question->title, $surveyParserData, true, true) }}</b></i>
                        <div id="barchart_blindspot_under_{{ $role->id }}_{{ $i }}"></div>
                        <br>
                    </div>
                    <?php $i++; ?>
                @endforeach
            </div>

            @if (count($role->underestimated) == 0)
                <b>{{ Lang::get('report.noUnderestimatedText') }}</b>
            @endif
        @overwrite

        @include('survey.reportpages.diagram', [
            'diagramName' => 'blinspotUnder',
            'titleText' => getReportText($survey, 'defaultBlindspotsUnderReportText', $reportTemplate)->subject,
            'bodyText' => getReportText($survey, 'defaultBlindspotsUnderReportText', $reportTemplate)->message,
            'pageBreak' => true,
            'noIncludeBox' => true,
            'titleLevel' => 'h4'
        ])
    @endforeach
@overwrite

@include('survey.reportpages.diagram', [
    'diagramName' => 'blindspots',
    'includeTitle' => Lang::get('report.blindspots'),
    'reportText' => getReportText($survey, 'defaultBlindspotsReportText', $reportTemplate),
    'pageBreak' => false,
    'isPage' => true
])
