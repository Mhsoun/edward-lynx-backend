<html>
<head>
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/report.css') }}">
    <script src="{{ asset('js/Chart.js') }}"></script>
    <script src="{{ asset('js/helpers.js') }}"></script>
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script src="https://code.jquery.com/jquery-1.11.2.min.js"></script>
    <script src="{{ asset('js/rgbcolor.js') }}"></script>
    <script src="{{ asset('js/StackBlur.js') }}"></script>
    <script src="{{ asset('js/canvg.js') }}"></script>
    <script src="{{ asset('js/tinycolor-min.js') }} "></script>

    <?php
        $isIndividual = \App\SurveyTypes::isIndividualLike($survey->type);
        $isGroup = \App\SurveyTypes::isGroupLike($survey->type);
        $isNormal = $survey->type == \App\SurveyTypes::Normal;
        $isProgress = $survey->type == \App\SurveyTypes::Progress;
        $isGroupReport = $isIndividual && $toEvaluate == null;

        //Determine the self role name
        $selfRoleName = "";

        if ($isIndividual) {
            if ($toEvaluate != null) {
                $selfRoleName = Lang::get('roles.self');
            } else {
                $selfRoleName = Lang::get('roles.candidates');
            }
        } else if ($isGroup) {
            $selfRoleName = $survey->toEvaluateRole()->name;
        }

        //Determine the manager id and self role id
        $selfRoleId = \App\SurveyReportHelpers::getSelfRoleId($survey, $toEvaluate);
        $managerRoleId = 0;

        if (!$isNormal) {
            $managerRoleId = \App\Roles::getRoleIdByName(
                Lang::get('roles.manager', [], 'en'),
                $survey->type);
        }

        $othersRoleName = Lang::get('roles.others');
        $othersRoleId = -1;

        //Indicates if the given role is valid in the given survey
        function isValidRole($survey, $role) {
            $isIndividual = $survey->type == \App\SurveyTypes::Individual || $survey->type == \App\SurveyTypes::Progress;
            $isGroup = \App\SurveyTypes::isGroupLike($survey->type);
            $isNormal = $survey->type == \App\SurveyTypes::Normal;

            return
                ($isIndividual && ($role->id != \App\Roles::selfRoleId() && $role->id != \App\Roles::candidatesRoleId()))
                || ($isGroup && $role->id != $survey->toEvaluateRole()->id)
                || $isNormal;
        }

        //Returns the answer values for the given question
        function getAnswerValues($questionId) {
            $answerType = \App\AnswerType::forQuestion($questionId);
            $maxValue = $answerType->maxValue();

            if (!$answerType->isNumeric()) {
                $values = array_map(function($value) use ($maxValue) {
                    return (object)[
                        'v' => ($value->value / $maxValue) * 100,
                        'f' => "" . $value->description
                    ];
                }, $answerType->values());
            } else if (count($answerType->valueExplanations()) > 0) {
                $values = array_merge(
                    [(object)['v' => 0, 'f' => ""]],
                    array_map(function($value) use ($maxValue) {
                    return (object)[
                        'v' => ($value->value / $maxValue) * 100,
                        'f' => ""
                    ];
                }, $answerType->values()));
            } else {
                $values = [
                    (object)['v' => 0, 'f' => '0%'],
                    (object)['v' => 70, 'f' => '70%'],
                    (object)['v' => 100, 'f' => '100%']
                ];
            }

            usort($values, function($x, $y) {
                return $x->v - $y->v;
            });

            return $values;
        }

        //Returns the given report text
        function getReportText($survey, $name, $reportTemplate = null) {
            return \App\Models\DefaultText::getReportText($survey, app()->getLocale(), $reportTemplate, $name);
        }

        //Returns the given report text for the given role
        function getReportTextForRole($survey, $name, $roleId, $reportTemplate = null) {
            return \App\Models\DefaultText::getReportTextForRole($survey, app()->getLocale(), $reportTemplate, $name, $roleId);
        }

        //Indicates if the given diagram should be included
        function includeDiagram($reportTemplate, $typeId) {
            if ($reportTemplate != null) {
                $diagram = $reportTemplate->diagrams()
                    ->where('typeId', '=', $typeId)
                    ->first();

                if ($diagram == null) {
                    return false;
                }

                return $diagram->includeDiagram;
            } else {
                return true;
            }
        }

        if (!isset($includeInGroupReport)) {
            $includeInGroupReport = null;
        }

        $surveyParserData = \App\EmailContentParser::createReportParserData($survey, $toEvaluate, $includeInGroupReport);
    ?>
</head>
<body>

@if (isset($autogenerate) && $autogenerate)
<div class="autogenerate-curtain">Please wait...</div>
@endif

<script type="text/javascript">
    var chartsToDraw = [];

    google.setOnLoadCallback(function() {
        for (var i in chartsToDraw) {
            chartsToDraw[i]();
        }

        $("#create").removeAttr("disabled");
    });

    var barWidth = 30;

    var defaultFontSize = 20;
    var defaultFontName = "Calibri";

    var diagramTextStyle = {
        fontSize: defaultFontSize
    };

    var firstPageTextStyle = {
        fontSize: 28
    };

    var legendTextStyle = {
        fontSize: 22
    };

    var axisLineStyle = {
        color: "#8E8E8E"
    };

    function setImage(chartDiv, chart, className) {
        chartDiv.innerHTML = '<img ' + (className !== undefined ? 'class="' + className +  '"' : '') + ' src="' + chart.getImageURI() + '">';
    }

    function setRadarImage(radarDiv, img) {
        radarDiv.innerHTML = "<img src=" + img.value + ">";
    }

    var pieColor1 = "#A5A5A5";
    var pieColor2 = "#61487A";

    var summaryColor = "#4C1662";
    var averageValueColor = "#4C1662";

    var selfColor = "#6B4190";
    var managerColor = "#C3601D";

    var othersColor = "#F44336";
    var orangeColor = "#F79646";

    @if ($isProgress)
        selfColor = "#AA80B4";
        othersColor = orangeColor;
    @endif

    //Returns the color for the given role
    @if ($isGroup)
        var roleColors = {};
        roleColors[{{ $selfRoleId }}] = selfColor;
        var colorsList = [orangeColor, '#3BCE3B', '#3BAFCC', '#CC3B3B', '#CCB13B']
        var colorsIndex = 0;
        @foreach ($rolesByCategory as $role)
            @if ($role->id != $selfRoleId)
                roleColors[{{ $role->id }}] = colorsList[colorsIndex];
                colorsIndex = Math.min(colorsIndex + 1, colorsList.length - 1);
            @endif
        @endforeach

        function getRoleColor(roleId) {
            if (roleColors[roleId] !== undefined) {
                return roleColors[roleId];
            }

            return othersColor;
        }
    @else
        function getRoleColor(roleId, darken) {
            var color = "";
            if (roleId == {{ $selfRoleId }}) {
                color = selfColor;
            } else if (roleId == -1) {
                color = othersColor;
            } else {
                color = orangeColor;
            }

            if (darken !== undefined && darken) {
                color = tinycolor(color).darken(20).toString();
            }

            return color;
        }
    @endif

    google.load('visualization', '1', { packages: ['corechart'] });

    var longestCategoryName = 0;
    var longestCategoryNameText = "";
    @foreach ($categories as $category)
        if ({{ strlen($category->name) }} > longestCategoryNameText.length) {
            longestCategoryNameText = {!! json_encode($category->name) !!};
            longestCategoryName = longestCategoryNameText.length;
        }
    @endforeach

    var defaultIntScale = [
        { v: 0, f: '0 %' },
        { v: 0.7, f: '70 %' },
        { v: 1, f: '100 %' }
    ];

    //Creates a scale with a 'thick' line at 70%
    function getScaleWithThick7Line(chartWidth, thickness) {
        var line7Before = [];
        var line7After = [];
        var delta = 1 / chartWidth;

        for (var i = 1; i <= thickness / 2; i++) {
            line7After.push({ v: (0.7 + delta * i), f: '' });
        }

        return [{ v: 0, f: '0 %' }]
            .concat(line7Before)
            .concat([{ v: 0.7, f: '70 %' }])
            .concat(line7After)
            .concat([{ v: 1, f: '100 %' }]);
    }

    //Calculates the size of the given text
    function calculateTextSize(text, size, font) {
        var span = null;
        if (!window.widthSpan) {
            span = jQuery("<span>");
            span.hide();
            span.appendTo(document.body);
            window.widthSpan = span;
        } else {
            span = widthSpan;
        }

        span.text(text);
        span.css("font-size", size);
        span.css("font", font);
        return span.width();
    }

    //Converts the given SVG to PNG
    function svgToPng(svg) {
        var canvas = document.createElement('canvas');
        canvg(canvas, svg)
        return canvas.toDataURL("image/png");
    }

    //Returns the given image square
    function getImageSquare(color, size) {
        size = size || 1;

        var svg =   "<svg width='" + size + "' height='" + size + "'>"
                  +     "<rect fill='" + color + "' width='" + size + "' height='" + size + "'></rect>"
                  + "</svg>";

        return svgToPng(svg);
    }
</script>
<div class="container topContainer">
    @include('survey.reportpages.menu', ['parserData' => $surveyParserData])
</div>

<div class="page container">
    @if ($survey->type == \App\SurveyTypes::Progress)
        @include('survey.reports.progress')
    @elseif ($survey->type == \App\SurveyTypes::Individual)
        @include('survey.reports.360')
    @elseif (\App\SurveyTypes::isGroupLike($survey->type))
        @include('survey.reports.group')
    @elseif ($survey->type == \App\SurveyTypes::Normal)
        @include('survey.reports.normal')
    @endif
</div>

<script>
    $(document).ready(function () {
        $("#create").on("click", function (e) {
            e.preventDefault();

            //Delete include checkboxes
            $(".includeCheckbox").remove();

            if (typeof(recalculatePageBreaks) == "function") {
                recalculatePageBreaks();
            }

            //Remove last page break
            removeLastPageBreak();

            //Get all HTML content inside page div
            $("#htmlContent").val($(".page").html());
            $('#reportForm').submit();
        });

        replaceImages();
    });

    //Removes the last page break
    function removeLastPageBreak() {
        var pageBreaks = $(".pageBreak");
        $(pageBreaks[pageBreaks.length - 1]).removeClass("pageBreak");
    }

    //Toggles the visibility of the current graph
    function toggleShowInReport(element, pageId) {
        element = $(element);
        var page = $("#" + pageId);

        if (element.is(":checked")) {
            page.show();
            return true;
        } else {
            page.hide();
            return false;
        }

        if (typeof(recalculatePageBreaks) == "function") {
            recalculatePageBreaks();
        }
    }

    //Replace images with base64 images
    function replaceImages() {
        $(".toBase64").each(function(i, element) {
            element = $(element);
            Helpers.convertImgToBase64URL(element.attr("src"), function(base64) {
                element.attr("src", base64);
                element.removeClass("toBase64");
            });
        });
    }

    //Result calcuations
    var resultCalcuations = $("#resultCalcuations");
    function toggleResutlCalcuations(show) {
        if (show) {
            resultCalcuations.show();
        } else {
            resultCalcuations.hide();
        }
    }

    //Shows the edit text
    function showEditText(titleId, textId, editBoxId) {
        var editBox = $("#" + editBoxId);
        var title = $("#" + titleId);
        var text = $("#" + textId);

        editBox.find(".editText").val(text.html());
        editBox.find(".editTitle").val(title.text());

        editBox.show();
        text.hide();
        title.hide();
    }

    //Saves the edit text
    function saveEditText(titleId, textId, editBoxId) {
        var editBox = $("#" + editBoxId);
        var title = $("#" + titleId);
        var text = $("#" + textId);

        text.html(editBox.find(".editText").val());
        title.text(editBox.find(".editTitle").val());

        editBox.hide();
        text.show();
        title.show();
    }

    //Changes the language
    function changeLanguage() {
        var newLang = $("#lang").val();
        @if ($toEvaluate != null)
            document.location = {!! json_encode(action('ReportController@showReport', [
                $survey->id,
                'recipientId' => $toEvaluate->recipientId,
                'lang' => ''
            ])) !!} + newLang;
        @else
            document.location = {!! json_encode(action('ReportController@showReport', [
                $survey->id,
                'includeInGroupReport' => $includeInGroupReport != null ? implode(',', $includeInGroupReport) : '',
                'lang' => ''])) !!} + newLang;
        @endif
    }

    @if (isset($autogenerate) && $autogenerate)
    $(window).on('load', function() {
        setTimeout(function() { $('#create').click() }, 4500);
    });
    @endif
</script>
</body>
</html>
