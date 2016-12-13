<?php
    //Returns the display value for the given value
    function getDisplayValue($answerType, $value) {
        if ($answerType->isNumeric()) {
            return ($value / 100) * $answerType->maxValue();
        } else {
            return $value . "%";
        }
    }

    //Groups the question by score
    function groupByScore($questions, $isGroup = false) {
        $scoreGroups = [];

        foreach ($questions as $question) {
            if ($isGroup) {
                $score = intval($question->average);
            } else {
                $score = intval($question->others);
            }

            if (!array_key_exists($score, $scoreGroups)) {
                $scoreGroups[$score] = [];
            }

            array_push($scoreGroups[$score], $question);
        }

        return array_values($scoreGroups);
    }

    //Extract questions
    function extractQuestions($questions, $isGroup = false) {
        $extracted = [];

        if (count($questions) == 0) {
            return $extracted;
        }

        //Group by score
        $scoreGroups = array_map(function($group) use ($isGroup) {
            if ($isGroup) {
                return \App\SurveyReportHelpers::groupQuestionsByCategory($group);
            } else {
                return \App\SurveyReportHelpers::groupQuestionsByCategory($group, null, false);
            }
        }, groupByScore($questions, $isGroup));

        $requiredNumQuestions = min(count($questions), 5);

        //For each score group: extract different categories.
        foreach ($scoreGroups as $group) {
            $categoryLastQuestion = [];
            $numCategories = count($group);
            for ($i = 0; $i < $numCategories; $i++) {
                $categoryLastQuestion[$i] = -1;
            }

            //Select first from category 1, then 2, ...
            $categoryIndex = 0;
            while (true) {
                $currentCategory = $group[$categoryIndex];
                $nextQuestionIndex = $categoryLastQuestion[$categoryIndex] + 1;

                if ($nextQuestionIndex < count($currentCategory->questions)) {
                    $nextQuestion = $currentCategory->questions[$nextQuestionIndex];
                    $categoryLastQuestion[$categoryIndex] = $nextQuestionIndex;
                    array_push($extracted, $nextQuestion);

                    if (count($extracted) == $requiredNumQuestions) {
                        break;
                    }
                }

                $categoryIndex = ($categoryIndex + 1) % $numCategories;

                //If we have depleted each category, stop.
                $allDepleted = true;
                for ($i = 0; $i < $numCategories; $i++) {
                    if ($categoryLastQuestion[$i] < count($group[$i]->questions) - 1) {
                        $allDepleted = false;
                        break;
                    }
                }

                if ($allDepleted) {
                    break;
                }
            }

            if (count($extracted) == $requiredNumQuestions) {
                break;
            }
        }

        return $extracted;
    }

    function extractQuestions2($questions, $isHighest, $isGroup = false) {
        usort($questions, function($x, $y) use ($isHighest, $isGroup) {
            $averageX = 0;
            $averageY = 0;

            if ($isGroup) {
                $averageX = $x->average;
                $averageY = $y->average;
            } else {
                $averageX = $x->others;
                $averageY = $y->others;
            }

            if ($averageX > $averageY) {
                return $isHighest ? -1 : 1;
            } else if ($averageX < $averageY) {
                return $isHighest ? 1 : -1;
            } else {
                return 0;
            }
        });

        return array_slice($questions, 0, 5);
    }

    /**
    * Returns the report order or the default value
    */
    function reportOrderOrDefault($reportTemplate, $pageId, $default) {
        if ($reportTemplate != null) {
            return $reportTemplate->orderFor($pageId);
        } else {
            return $default;
        }
    }
?>

<script type="text/javascript">
    @if (!$isNormal)
        function drawQuestionDiagram(chartDivName, scale, selfTitle, selfValue, othersRoleId, othersTitle, othersValue, isProgress) {
            chartsToDraw.push(drawChart);

            function drawChart() {
                drawDiagramWithQuestion(
                    document.getElementById(chartDivName),
                    scale,
                    selfTitle,
                    selfValue,
                    othersRoleId,
                    othersTitle,
                    othersValue,
                    isProgress);
            }
        }

        function drawDiagramWithQuestion(chartElement, scale, selfTitle, selfValue, othersRoleId, othersTitle, othersValue, isProgress) {
            var dataPoints = [
                ["Title", "Value", { role: "style" }],
                [othersTitle, othersValue, othersRoleId == -1 ? othersColor : orangeColor],
                [selfTitle, selfValue, selfColor]
            ];

            var data = google.visualization.arrayToDataTable(dataPoints);
            var view = new google.visualization.DataView(data);
            view.setColumns([0, 1, {
                calc: 'stringify',
                sourceColumn: 1,
                type: 'string',
                role: 'annotation'
            }, 2]);

            var minValue = scale[0].v;

            //If the scale don't contain zero, add it.
            if (minValue != 0) {
                var minValueText = "0 %";
                if (scale[0].f.indexOf("%") == -1) {
                    minValueText = "0";
                }

                scale = [{ v: 0, f: minValueText}].concat(scale);
                minValue = 0;
            }

            // var namesOffset = Math.max(selfTitle.length, othersTitle.length) * 12;
            var namesOffset = calculateTextSize(
                selfTitle.length > othersTitle.length ? selfTitle : othersTitle,
                defaultFontSize,
                defaultFontName);

            var scaleFontSize = defaultFontSize;
            var height = barWidth * 2 + 100;

            var options = {
                title: '',
                chartArea: { width: ((1 - namesOffset / 1200 - 0.05) * 100) + '%', height: '60%', left: namesOffset },
                height: height,
                width: 1200,
                bar: { groupWidth: barWidth },
                hAxis: {
                    title: '',
                    minValue: minValue,
                    maxValue: scale[scale.length - 1].v,
                    ticks: scale,
                    gridlines: axisLineStyle,
                    textStyle: {
                        fontSize: scaleFontSize
                    }
                },
                vAxis: {
                    title: '',
                },
                legend: {
                    position: 'none'
                },
                fontSize: defaultFontSize,
                fontName: defaultFontName,
                annotations: {
                    alwaysOutside: true
                }
            };

            var chart = new google.visualization.BarChart(chartElement);

            // Wait for the chart to finish drawing before calling the getIm geURI() method.
            google.visualization.events.addListener(chart, 'ready', function () {
                setImage(chartElement, chart);
            });

            chart.draw(view, options);
        }
    @else
        function drawQuestionDiagram(chartDivName, scale, value) {
            chartsToDraw.push(drawChart);

            function drawChart() {
                var dataPoints = [
                    ["Title", "Value", { role: "style" }],
                    ["", value, selfColor]
                ];

                var data = google.visualization.arrayToDataTable(dataPoints);
                var view = new google.visualization.DataView(data);
                view.setColumns([0, 1, {
                    calc: 'stringify',
                    sourceColumn: 1,
                    type: 'string',
                    role: 'annotation'
                }, 2]);

                var minValue = scale[0].v;

                //If the scale don't contain zero, add it.
                if (minValue != 0) {
                    scale = [{ v: 0, f: '0' }].concat(scale);
                    minValue = 0;
                }

                var options = {
                    title: '',
                    chartArea: { width: '70%', height: '60%' },
                    height: barWidth + 100,
                    width: 1200,
                    bar: { groupWidth: barWidth },
                    hAxis: {
                        title: '',
                        minValue: minValue,
                        maxValue: scale[scale.length - 1].v,
                        ticks: scale,
                    },
                    vAxis: {
                        title: '',
                    },
                    legend: {
                        position: 'none'
                    },
                    fontSize: defaultFontSize,
                    fontName: defaultFontName,
                    annotations: {
                        alwaysOutside: true
                    }
                };

                var chartDiv = document.getElementById(chartDivName);
                var chart = new google.visualization.BarChart(chartDiv);

                // Wait for the chart to finish drawing before calling the getIm geURI() method.
                google.visualization.events.addListener(chart, 'ready', function () {
                    setImage(chartDiv, chart);
                });

                chart.draw(view, options);
            }
        }
    @endif
</script>
