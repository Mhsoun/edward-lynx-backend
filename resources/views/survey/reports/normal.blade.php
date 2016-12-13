@include('survey.reportpages.shared')

<!-- Average -->
@include('survey.reportpages.average', ['categories' => $categories])

<script type="text/javascript">
    function drawValuesChart(chartName, values, allAverage) {
        var dataPoints = [["Title", "Procentage", { role: "style" }]];
        var longestValueName = "";

        //Filter out values without average
        values = values.filter(function (value) {
            return value.value != null;
        });

        values.forEach(function (value) {
            dataPoints.push([value.name, value.value, averageValueColor]);

            if (value.name.length > longestValueName.length) {
                longestValueName = value.name;
            }
        });

        if (allAverage != undefined) {
            dataPoints.push([{!! json_encode(Lang::get('surveys.selectAll')) !!}, allAverage, '#166053']);
        }

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

        var leftOffset = calculateTextSize(longestValueName, 25);
        var areaWidth = 700;
        var chartWidth = 100 + leftOffset + areaWidth;

        var options = {
            title: "",
            titleTextStyle: { fontSize: 25, bold: true },
            height: dataPoints.length * (barWidth) + 100,
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
                    fontSize: 25
                }
            },
            fontSize: 25,
            fontName: defaultFontName,
            annotations: {
                alwaysOutside: true,
            }
        };

        var chartDiv = document.getElementById(chartName);
        var chart = new google.visualization.BarChart(chartDiv);

        google.visualization.events.addListener(chart, 'ready', function () {
            setImage(chartDiv, chart);
        });

        chart.draw(view, options);
    }
</script>

<!-- All questions -->
@include('survey.reportpages.allQuestionsNormal', ['extraAnswersByCategories' => $extraAnswersByCategories])

<!-- All categories -->
@include('survey.reportpages.resultsPerCategoryNormal', ['extraAnswersByCategoriesSummary' => $extraAnswersByCategoriesSummary])

<!-- All extra questions -->
@include('survey.reportpages.resultsPerExtraQuestion', ['categoriesByExtraAnswer' => $categoriesByExtraAnswer])

<!-- Detailed answer summary per category -->
{{-- @include('survey.reportpages.detailedanswersummary', [
    'categories' => $categories,
    'categoryAnswerFrequency' => $categoryAnswerFrequency
]) --}}

<!-- Participants information -->
@include('survey.reportpages.participants')
