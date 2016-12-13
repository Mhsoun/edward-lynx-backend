<?php
    $isValid = false;
    $roles = [];
    $selfRoleActualId = \App\Roles::selfRoleId();

    $recipients = [];

    if ($isGroupReport) {
        $recipients = $survey->recipients()
            ->where('hasAnswered', '=', true)
            ->get();
    } else if ($isIndividual) {
        $recipients = $survey->recipients()
            ->where('hasAnswered', '=', true)
            ->where('invitedById', '=', $toEvaluate->recipientId)
            ->get();
    } else {
        $recipients = $survey->recipients()
            ->where('hasAnswered', '=', true)
            ->get();
    }

    foreach ($recipients as $recipient) {
        $roleId = $recipient->roleId;
        $roleName = $roleNames[$roleId];

        if ($isGroupReport && $roleId == $selfRoleActualId) {
            $roleId = $selfRoleId;
        }

        if ($roleId == $selfRoleId) {
            $roleName = $selfRoleName;
        }

        $role = null;
        if (!array_key_exists($roleId, $roles)) {
            $role = (object)[
                'id' => $roleId,
                'name' => $roleName,
                'toEvaluate' => $roleId == $selfRoleId,
                'count' => 0
            ];

            $roles[$roleId] = $role;
        } else {
            $role = $roles[$roleId];
        }

        $role->count++;
    }

    $roles = \App\SurveyReport::sortByRoleId($roles, $survey->type);
?>

<script type="text/javascript">
    chartsToDraw.push(drawResponseRate);

    function drawResponseRate() {
        var dataPoints = [["Title", "Procentage", { role: "style" }]];

        <?php $roleNameLength = 0 ?>
        var responseRateScale = [];
        var max = 0;

        var roleCounts = {};
        roleCounts[0] = true;

        @foreach ($roles as $role)
            dataPoints.push([
                {!! json_encode($role->name) !!},
                {{ $role->count }},
                getRoleColor({{ $role->id }})
            ]);

            var count = {{ $role->count }};
            // max = Math.max(count, max);
            roleCounts[count] = true;

            <?php $roleNameLength += strlen($role->name) * 17 + 10; ?>
        @endforeach

        // for (var i = 0; i <= max; i++) {
        //     responseRateScale.push({
        //         v: i,
        //         f: "" + i
        //     });
        // }

        for (var count in roleCounts) {
            responseRateScale.push({
                v: count,
                f: "" + count
            });
        }

        var data = google.visualization.arrayToDataTable(dataPoints);
        var view = new google.visualization.DataView(data);

        var vAxis = {};

        if (max < 12) {
            vAxis = {
                title: {!! json_encode(Lang::get('report.numberOfReviewers')) !!},
                ticks: responseRateScale,
                minValue: 0
            };
        } else {
            vAxis = {
                title: {!! json_encode(Lang::get('report.numberOfReviewers')) !!},
                minValue: 0
            };
        }

        var options = {
            title: "",
            titleTextStyle: { fontSize: 20, bold: true },
            height: 500,
            width: {{ 200 + $roleNameLength }},
            chartArea: { width: '90%', height: '70%', left: 80 },
            bar: { groupWidth: barWidth * 2 },
            legend: { position: "left" },
            vAxis: vAxis,
            hAxis: {
                title: '',
            },
            fontSize: 20,
            fontName: defaultFontName,
            annotations: {
                alwaysOutside: true
            }
        };

        var chartDiv = document.getElementById('responseRateChart');
        var chart = new google.visualization.ColumnChart(chartDiv);

        // Wait for the chart to finish drawing before calling the getIm geURI() method.
        google.visualization.events.addListener(chart, 'ready', function () {
            setImage(chartDiv, chart);
        });

        chart.draw(view, options);
    }
</script>

@section('diagramContent')
    <div id="responseRateChart"></div>
@overwrite

@include('survey.reportpages.diagram', [
    'diagramName' => 'responseRate',
    'includeTitle' => Lang::get('report.responseRate'),
    'reportText' => getReportText($survey, 'defaultResponseRateReportText', $reportTemplate),
    'pageBreak' => false
])
