@section('diagramContent')
    <?php $i = 0; ?>
    @foreach ($allQuestions as $question)
        <div class="description">
            {{ $i + 1 }}. <i>{{ \App\EmailContentParser::parse($question->title, $surveyParserData, true) }}</i>
            <div id="question_diagram_{{ $i }}" class="questionDiagram"></div>
            <?php $i++; ?>
        </div>
    @endforeach

    <?php $i = 0; ?>
    @foreach ($allQuestions as $question)
        <script type="text/javascript">
            drawQuestionDiagram(
                "question_diagram_{{ $i }}",
                {!! json_encode(getAnswerValues($question->id)) !!},
                {!! json_encode($selfRoleName) !!},
                {{ $question->self }},
                {!! json_encode(-1) !!},
                {!! json_encode($othersRoleName) !!},
                {{ $question->others }},
                {!! json_encode($survey->createUserReports) !!});
        </script>
        <?php $i++; ?>
    @endforeach

    <?php
        $answerType = \App\AnswerType::forQuestion($allQuestions[0]->id);
        $namesOffset = max(strlen($selfRoleName), strlen($othersRoleName)) * 12;
        $chartWidth = 1200 * 0.7;
        $deltaBar = $chartWidth / (count($answerType->values()));
        $markerHeight = 10;
        $markerBarWidth = 2;
    ?>

    @if (count($answerType->valueExplanations()) > 0)
        <div id="scaleExplanationBox" class="questionDiagram">
            <svg id="scaleExplanation" style="overflow: hidden;" height="50" width="1200">
                <g>
                    <rect fill="#427AFF" stroke-width="0" stroke="none" height="2" width="{{ $chartWidth }}"
                          y="{{ $markerHeight / 2 }}" x="{{ $namesOffset }}"></rect>
                    @for ($i = 0; $i <= 10; $i += 2)
                        <rect fill="#FF7919" stroke-width="0" stroke="none" height="{{ $markerHeight }}" width="{{ $markerBarWidth }}"
                              y="0"
                              x="{{ $namesOffset + $deltaBar * $i - $markerBarWidth / 2 }}"></rect>
                    @endfor
                </g>
                <g>
                    <?php $i = 0; ?>
                    @foreach ($answerType->valueExplanations() as $value)
                        <g>
                            <?php
                                $x = $namesOffset + $deltaBar * (($value->lower + $value->upper) * 0.5 * 10);
                                $lines = explode('\n', $value->explanation);
                            ?>

                            <text fill="#000" y="{{ 20 + $markerHeight }}" stroke-width="0" stroke="none"
                                  font-size="16" font-weight="bold" font-family="Calibri" text-anchor="middle">
                                @for ($j = 0; $j < count($lines); $j++)
                                    <tspan dy="{{ $j > 0 ? 15 : 0 }}" x="{{ $x }}">{{ $lines[$j] }}</tspan>
                                @endfor
                            </text>
                        </g>
                        <?php $i++; ?>
                    @endforeach
                </g>
            </svg>
        </div>

        <script type="text/javascript">
            function b64EncodeUnicode(str) {
                return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g, function(match, p1) {
                    return String.fromCharCode('0x' + p1);
                }));
            }

            $(document).ready(function() {
                //Convert the scale explanation to an image
                var svg = document.getElementById('scaleExplanation');

                if (svg != null) {
                    var svgData = new XMLSerializer().serializeToString(svg);

                    var canvas = document.createElement("canvas");
                    var ctx = canvas.getContext("2d");

                    var svgSize = svg.getBoundingClientRect();
                    canvas.width = svgSize.width;
                    canvas.height = svgSize.height;

                    var img = document.createElement("img");
                    img.setAttribute("src", "data:image/svg+xml;base64," + b64EncodeUnicode(svgData));

                    img.onload = function() {
                        ctx.drawImage(img, 0, 0);
                        $("#scaleExplanationBox").html("<img src='" + canvas.toDataURL("image/png") + "'>");
                    };
                }
            });
        </script>
    @endif
@overwrite

@include('survey.reportpages.diagram', [
    'diagramName' => 'allQuestions',
    'includeTitle' => Lang::get('report.allQuestions'),
    'titleText' => getReportText($survey, 'defaultRatingsPerQuestionReportText', $reportTemplate)->subject,
    'bodyText' => getReportText($survey, 'defaultRatingsPerQuestionReportText', $reportTemplate)->message,
    'pageBreak' => true,
    'visible' => includeDiagram($reportTemplate, \App\Models\DefaultText::RatingsPerQuestionReportText)
])
