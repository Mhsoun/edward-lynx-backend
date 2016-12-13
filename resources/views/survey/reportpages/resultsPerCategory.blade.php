<?php
    $maxNumRoles = 0;
    foreach ($categoriesByRole as $category) {
        $maxNumRoles = max($maxNumRoles, count($category->roles));
    }

    $yesOrNoCategories = \App\SurveyReportHelpers::groupQuestionsByCategory($yesOrNoQuestions, null, false);
    $commentsCategories = \App\SurveyReportHelpers::groupQuestionsByCategory($comments, null, false);

    $resultsPerCategory = [];
    foreach($categoriesByRole as $category) {
        $newCategory = clone $category;

        $categoryYesOrNo = \App\SurveyReportHelpers::findCategoryById($yesOrNoCategories, $newCategory->id);

        if ($categoryYesOrNo != null) {
            $newCategory->yesOrNoQuestions = $categoryYesOrNo->questions;
        } else {
            $newCategory->yesOrNoQuestions = [];
        }

        $categoryComments = \App\SurveyReportHelpers::findCategoryById($commentsCategories, $newCategory->id);

        if ($categoryComments != null) {
            $newCategory->comments = $categoryComments->questions;
        } else {
            $newCategory->comments = [];
        }

        array_push($resultsPerCategory, $newCategory);
    }
?>

<script type="text/javascript">
    //Draws the given yes or no question
    function drawYesOrNo(questionId, yesRatio, noRatio) {
        var dataPoints = [];

        dataPoints.push(['Question', 'Percentage']);
        dataPoints.push([
            {!! json_encode(Lang::get('answertypes.yes')) !!},
            yesRatio
        ]);

        dataPoints.push([
            {!! json_encode(Lang::get('answertypes.no')) !!},
            noRatio
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
            height: 350,
            chartArea: { width: "100%", height: "100%", left: 0, top: 0 },
            fontSize: defaultFontSize,
            fontName: defaultFontName,
            legend: { position: "right", alignment: "center" },
            pieSliceBorderColor: 'none',
            sliceVisibilityThreshold: 0,
            colors: [pieColor1, pieColor2]
        };

        var chartDiv = document.getElementById('yes_or_no_' + questionId);
        var chart = new google.visualization.PieChart(chartDiv);

        // Wait for the chart to finish drawing before calling the getIm geURI() method.
        google.visualization.events.addListener(chart, 'ready', function () {
            setImage(chartDiv, chart, 'pieChart');
        });

        chart.draw(view, options);
    }
</script>

@foreach($resultsPerCategory as $category)
    <script type="text/javascript">
        chartsToDraw.push(drawParticipantType);
        function drawParticipantType() {
            var dataPoints = [];
            dataPoints.push(['Title', 'Percentage', { role: 'style' }]);
            var numRoles = 0;

            @foreach ($category->roles as $role)
                @if ($role->average != null)
                    dataPoints.push([
                        {!! json_encode($role->name) !!},
                        {{ $role->average != null ? round($role->average, 2) : null }},
                        getRoleColor({{ $role->id }})
                    ]);

                    numRoles++;
                @endif
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

            var chartWidth = 1000;

            var options = {
                width: chartWidth,
                height: numRoles * (barWidth * 2 + 10) + 30,
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

            var chartDiv = document.getElementById('role_comparision_{!! $category->id !!}');
            var chart = new google.visualization.BarChart(chartDiv);

            // Wait for the chart to finish drawing before calling the getIm geURI() method.
            google.visualization.events.addListener(chart, 'ready', function () {
                setImage(chartDiv, chart);
            });

            chart.draw(view, options);
        }

        @foreach ($category->yesOrNoQuestions as $question)
            chartsToDraw.push(function() {
                drawYesOrNo(
                    {{ $question->id }},
                    {{ round($question->yesRatio * 100) }},
                    {{ round($question->noRatio * 100) }});
            });
        @endforeach
    </script>
@endforeach

<?php
    $breakdownPages = [];
    $breakdownPage = [];
    $breakdownPageCount = 0;
    $maxCount = 1;

    foreach ($resultsPerCategory as $category) {
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
    <div>
        <?php $i = 0; ?>
        @foreach ($breakdownPages as $page)
            @foreach ($page as $category)
                <div style="margin-bottom: 40px;" class="categoryResults pageBreak">
                    <h4>{{ $category->name }}</h4>
                    <div id="{!! 'role_comparision_' . $category->id !!}"></div>

                    @if (count($category->comments) > 0)
                        <br>
                        <br>
                    @endif

                    @foreach ($category->yesOrNoQuestions as $question)
                        <i class="description"><b>{{
                            \App\EmailContentParser::parse($question->title, $surveyParserData, true, true)
                        }}</b></i>
                        <div id="{!! 'yes_or_no_' . $question->id !!}"></div>
                    @endforeach

                    @if (count($category->comments) > 0)
                        <br>
                        <br>
                    @endif

                    @foreach ($category->comments as $question)
                        <i class="description"><b>{{
                            \App\EmailContentParser::parse($question->title, $surveyParserData, true, true)
                        }}</b></i>
                        <br>

                        @foreach (\App\ArrayHelpers::shuffle($question->answers) as $answer)
                            <span class="commentAnswer">&bull; {{ $answer->text }}</span>
                            <br>
                        @endforeach
                    @endforeach
                </div>

                <?php $i++ ?>
            @endforeach
        @endforeach
    </div>
@overwrite

@include('survey.reportpages.diagram', [
    'diagramName' => 'resultsPerCategory',
    'includeTitle' => Lang::get('report.resultsPerCategory'),
    'reportText' => getReportText($survey, 'defaultResultsPerCategoryReportText', $reportTemplate),
    'pageBreak' => false,
    'isPage' => true
])
