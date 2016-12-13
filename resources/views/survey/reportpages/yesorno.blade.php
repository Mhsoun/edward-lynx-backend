<?php $i = 0;?>
@foreach ($yesOrNoQuestions as $question)
    <script type="text/javascript">
        chartsToDraw.push(drawYesOrNo);

        function drawYesOrNo() {
            var dataPoints = [];

            dataPoints.push(['Question', 'Procentage']);
            dataPoints.push([
                {!! json_encode(Lang::get('answertypes.yes')) !!},
                {{ round($question->yesRatio * 100) }}
            ]);

            dataPoints.push([
                {!! json_encode(Lang::get('answertypes.no')) !!},
                {{ round($question->noRatio * 100) }}
            ]);

            var data = google.visualization.arrayToDataTable(dataPoints);
            var view = new google.visualization.DataView(data);

            view.setColumns([0, 1, {
                calc: 'stringify',
                sourceColumn: 1,
                type: 'string',
                role: 'annotation'
            }]);

            var options = {
                width: 500,
                height: 500,
                chartArea: { width: "80%", height: "80%" },
                fontSize: defaultFontSize,
                fontName: defaultFontName,
                legend: { position: "right", alignment: "center" },
                pieSliceBorderColor: 'none',
                sliceVisibilityThreshold: 0,
                colors: [pieColor1, pieColor2]
            };

            var chartDiv = document.getElementById('piechart_yesorno_{{ $i }}');
            var chart = new google.visualization.PieChart(chartDiv);

            // Wait for the chart to finish drawing before calling the getIm geURI() method.
            google.visualization.events.addListener(chart, 'ready', function () {
                setImage(chartDiv, chart);
            });

            chart.draw(view, options);
        }
    </script>

    <?php $i++; ?>
@endforeach

@if (count($yesOrNoQuestions) > 0)
    <?php
        $yesOrNoPages = [];
        $yesOrNoPage = [];
        $yesOrNoPageCount = 0;

        foreach ($yesOrNoQuestions as $question) {
            if ($yesOrNoPageCount + 1 > 3) {
                array_push($yesOrNoPages, $yesOrNoPage);
                $yesOrNoPage = [];
                $yesOrNoPageCount = 0;
            }

            array_push($yesOrNoPage, $question);
            $yesOrNoPageCount++;
        }

        array_push($yesOrNoPages, $yesOrNoPage);
    ?>

    @section('diagramContent')
        <?php $i = 0; $pageIndex = 0; ?>
        @foreach ($yesOrNoPages as $page)
            <div class="{{ $pageIndex == count($yesOrNoPages) - 1 ? '' : 'pageBreak' }}">
                @foreach ($page as $question)
                    <b class="description">{{ $question->category }}</b>
                    <br>
                    <i class="description"><b>{{
                        \App\EmailContentParser::parse($question->title, $surveyParserData, true, true)
                    }}</b></i>
                    <div id="piechart_yesorno_{{ $i }}" style="margin-left: 100px;"></div>
                    <?php $i++; ?>
                @endforeach
            </div>
            <?php $pageIndex++; ?>
        @endforeach
    @overwrite

    @include('survey.reportpages.diagram', [
        'diagramName' => 'yesOrNoPage',
        'includeTitle' => Lang::get('report.yesOrNo'),
        'titleText' => getReportText($survey, 'defaultYesOrNoReportText', $reportTemplate)->subject,
        'bodyText' => getReportText($survey, 'defaultYesOrNoReportText', $reportTemplate)->message,
        'pageBreak' => false,
        'isPage' => true
    ])
@endif
