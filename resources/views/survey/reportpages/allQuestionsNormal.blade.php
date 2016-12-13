<script type="text/javascript">
    var extraQuestionValueColors = [selfColor, orangeColor, '#3BCE3B', '#3BAFCC', '#CC3B3B', '#CCB13B'];

    function drawFrequencyChart(chartName, values) {
        var dataPoints = [];

        dataPoints.push(['Answer']);
        var existsNA = false;
        values.forEach(function (value) {
            dataPoints[0].push(value.name);
            dataPoints[0].push({ role: 'annotation' });
            existsNA = value.answerFrequency.some(function (answer) {
                return answer == 'N/A';
            });
        });

        //Pad NA values
        if (existsNA) {
            values.forEach(function (value) {
                var hasNA = value.answerFrequency.some(function (answer) {
                    return answer == 'N/A';
                });

                if (!hasNA) {
                    value.answerFrequency.splice(0, 0, {
                        answer: "N/A",
                        count: 0,
                        frequency: 0
                    });
                }
            });
        }

        for (var i = 0; i < values[0].answerFrequency.length; i++) {
            var dataPoint = [];
            dataPoint.push(values[0].answerFrequency[i].answer + "");

            values.forEach(function (value) {
                dataPoint.push(value.answerFrequency[i].frequency);
                dataPoint.push(Math.round(value.answerFrequency[i].frequency * 100));
            });

            dataPoints.push(dataPoint);
        }

        var data = google.visualization.arrayToDataTable(dataPoints);
        var view = new google.visualization.DataView(data);

        var options = {
            width: 1000,
            height: 400,
            chartArea: { width: "60%", height: "80%" },
            bar: { groupWidth: "60%" },
            legend: { position: 'right' },
            vAxis: {
                minValue: 0,
                maxValue: 1,
                format: "percent",
                gridlines: axisLineStyle
            },
            tooltip: { trigger: 'none' },
            fontSize: defaultFontSize,
            fontName: defaultFontName,
            colors: extraQuestionValueColors,
            annotations: {
                alwaysOutside: true
            }
        };

        var chartDiv = document.getElementById(chartName);
        var chart = new google.visualization.ColumnChart(chartDiv);

        // Wait for the chart to finish drawing before calling the getIm geURI() method.
        google.visualization.events.addListener(chart, 'ready', function () {
            setImage(chartDiv, chart);
        });

        chart.draw(view, options);
    }

    <?php $i = 0; ?>
    @foreach ($extraAnswersByCategories as $category)
        @foreach ($category->questions as $question)
            @foreach ($question->extraQuestions as $extraQuestion)
                @if (count($extraQuestion->values) > 0)
                    chartsToDraw.push(function() {
                        drawValuesChart("all_questions_value_{{ $i }}", {!! json_encode(array_map(function ($value) {
                            return (object)[
                                'name' => $value->value,
                                'value' => $value->average != 0 ? $value->average : null,
                            ];
                        }, $extraQuestion->values)) !!}, {{ $question->average }});

                        drawFrequencyChart("all_questions_frequency_{{ $i }}", {!! json_encode(array_map(function ($value) {
                            return (object)[
                                'name' => $value->value,
                                'answerFrequency' => $value->answerFrequency,
                            ];
                        }, $extraQuestion->values)) !!});
                    });
                    <?php $i++; ?>
                @endif
            @endforeach
        @endforeach
    @endforeach
</script>

@section('diagramContent')
    <?php $i = 0; ?>
    @foreach ($extraAnswersByCategories as $category)
        <h4>{{ $category->name }}</h4>

        @foreach ($category->questions as $question)
            <i><b>{{ \App\EmailContentParser::parse($question->title, $surveyParserData, true, true) }}</b></i>
            <br>

            @foreach ($question->extraQuestions as $extraQuestion)
                @if (count($extraQuestion->values) > 0)
                    <i>{{ \App\EmailContentParser::parse($extraQuestion->name, $surveyParserData, true, true) }}</i>
                    <br>
                    <b>{{ Lang::get('report.average') }}</b>
                    <div id="all_questions_value_{{ $i }}"></div>

                    <b>{{ Lang::get('report.answerFrequency') }}</b>
                    <div id="all_questions_frequency_{{ $i }}"></div>

                    <br>
                    <?php $i++; ?>
                @endif
            @endforeach

            <br>
        @endforeach

        @foreach ($category->comments as $comment)
            <i><b>{{ \App\EmailContentParser::parse($comment->title, $surveyParserData, true, true) }}</b></i>
            <br>

            @foreach (\App\ArrayHelpers::shuffle($comment->answers) as $answer)
                <span class="commentAnswer">&bull; {{ $answer->text }}</span>
                <br>
            @endforeach
        @endforeach
    @endforeach
@overwrite

@include('survey.reportpages.diagram', [
    'diagramName' => 'allQuestions',
    'includeTitle' => Lang::get('report.allQuestions'),
    'reportText' => getReportText($survey, 'defaultRatingsPerQuestionReportText', $reportTemplate),
    'pageBreak' => true,
])
