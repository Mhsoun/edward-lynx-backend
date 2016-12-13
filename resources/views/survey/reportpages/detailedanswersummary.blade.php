<?php
    $summaryCategoryAnswerFrequency = $categoryAnswerFrequency;

    if (\App\SurveyTypes::isIndividualLike($survey->type)) {
        $summaryCategoryAnswerFrequency = $otherCategoryAnswerFrequency;
    }
?>

@foreach ($summaryCategoryAnswerFrequency as $category)
    <script type="text/javascript">
        chartsToDraw.push(drawSummaryPerCategory);

        function drawSummaryPerCategory() {
            var dataPoints = [];

            dataPoints.push([
                'Question',
                'Percentage',
                { role: 'annotation' }
            ]);

            @foreach ($category->answerFrequency as $answer)
                <?php
                    $value = 0;

                    if ($category->numAnswers > 0) {
                        $value = round(($answer->count / $category->numAnswers), 2);
                    }
                ?>

                dataPoints.push([
                    "{{ $answer->answer }}",
                    {{ $value }},
                    {{ round($value * 100) }},
                ]);
            @endforeach

            var data = google.visualization.arrayToDataTable(dataPoints);
            var view = new google.visualization.DataView(data);

            var options = {
                width: 850,
                height: 400,
                chartArea: { width: "80%", height: "80%" },
                bar: { groupWidth: "95%" },
                legend: { position:'none' },
                vAxis: {
                    minValue: 0,
                    maxValue: 1,
                    format: "percent",
                    gridlines: axisLineStyle
                },
                tooltip: { trigger: 'none' },
                fontSize: defaultFontSize,
                fontName: defaultFontName,
                colors: [summaryColor],
                annotations: {
                    alwaysOutside: true
                }
            };

            var chartDiv = document.getElementById('detailed_{!! $category->id !!}');
            var chart = new google.visualization.ColumnChart(chartDiv);

            // Wait for the chart to finish drawing before calling the getIm geURI() method.
            google.visualization.events.addListener(chart, 'ready', function () {
                setImage(chartDiv, chart);
            });

            chart.draw(view, options);
        }
    </script>
@endforeach

<?php
    $detailedAnswerPages = [];
    $detailedAnswerPage = [];
    $detailedAnswerPageCount = 0;
    $chartsPerPage = 8;

    foreach ($categories as $category) {
        if ($detailedAnswerPageCount + 1 > $chartsPerPage) {
            array_push($detailedAnswerPages, $detailedAnswerPage);
            $detailedAnswerPage = [];
            $detailedAnswerPageCount = 0;
        }

        array_push($detailedAnswerPage, $category);
        $detailedAnswerPageCount++;
    }

    array_push($detailedAnswerPages, $detailedAnswerPage);
?>

@section('diagramContent')
    @foreach ($detailedAnswerPages as $page)
        <div class="pageBreak">
            <div class="row twoColumnDiagramLarge">
                <?php $i = 0; ?>
                @foreach ($page as $category)
                    @if ($i % 2 == 0)
                        <div class="left detailedElement" style="clear: left;">
                            <h4>{{ $category->name }}</h4>
                            <div id="{!! 'detailed_' . $category->id !!}"></div>
                        </div>
                    @else
                        <div class="right detailedElement">
                            <h4>{{ $category->name }}</h4>
                            <div id="{!! 'detailed_' . $category->id !!}"></div>
                        </div>
                    @endif
                    <?php $i++; ?>
                @endforeach
            </div>
        </div>
    @endforeach
@overwrite

@include('survey.reportpages.diagram', [
    'diagramName' => 'detailedAnswer',
    'includeTitle' => Lang::get('report.detailed'),
    'reportText' => getReportText($survey, 'defaultDetailedAnswerSummaryReportText', $reportTemplate),
    'pageBreak' => false,
    'isPage' => true
])
