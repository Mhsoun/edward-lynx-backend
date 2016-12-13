<?php
    $allQuestionsRoles = \App\SurveyReportHelpers::fromRolesToQuestions($questionsByRoles);
    \App\SurveyReportHelpers::sortQuestionsByOrder($allQuestionsRoles, $questionOrders);
    $isComparison = $survey->compareAgainstSurvey != null;

    $numRoles = count($questionsByRoles);

    //Compute the colors
    $roleColors = [];
    foreach ($questionsByRoles as $role) {
        $color = null;
        if (array_key_exists($role->id, $wantedRoleColors)) {
            $color = clone $wantedRoleColors[$role->id];
        } else {
            $color = clone $wantedRoleColors[-1];
        }

        $roleColors[$role->id] = $color;
    }

    //Get the legend items
    $legend = [];
    foreach($questionsByRoles as $role) {
        $roleColor = $roleColors[$role->id];

        if (count($role->questions) > 0) {
            foreach ($role->questions[0]->data as $dataPoint) {
                if ($dataPoint->survey->compareAgainstSurvey != null) {
                    $color = $roleColor->olderColor;
                    $strokeColor = $roleColor->olderStrokeColor;
                } else {
                    $color = $roleColor->color;
                    $strokeColor = $roleColor->strokeColor;
                }

                $name = $role->name;
                if ($isComparison) {
                    $name = $role->name . " (" . $dataPoint->survey->endDate->format('d M Y') . ")";
                }

                array_push($legend, (object)[
                    'roleId' => $role->id,
                    'name' => $name,
                    'color' => $color,
                    'strokeColor' => $strokeColor,
                ]);
            }
        }
    }

    $answerType = \App\AnswerType::forQuestion($allQuestionsRoles[0]->id);

    $fullWidth = 1000;
    $maxRoleName = 0;
    foreach ($legend as $item) {
        $maxRoleName = max($maxRoleName, strlen($item->name));
    }

    $fullWidth += $maxRoleName * 21;

    $legendSpacingX = 25;
    $fullWidth += $legendSpacingX * 2;

    $chartWidth = $fullWidth * 0.6;
    $namesOffset = 400;

    $numValues = count($answerType->values());
    $deltaBar = $chartWidth / $numValues;
    $markerHeight = 10;
    $markerBarWidth = 2;
    $questionDiagramHeight = 100;


    $legendBlockSize = 20;
    // $topOffset = 26 + ($numRoles - 1) * 30;
    $topOffset = 26;
    $offsetX = $namesOffset;

    $diagramQuestions = [];
    $totalQuestionsHeight = 0;
    $scaleSpacing = 10;
    $scaleTick = 1;

    if (count($answerType->valueExplanations()) > 0) {
        $scaleTick = 2;
    }

    $q = 1;
    foreach ($allQuestionsRoles as $question) {
        $questionTitle = \App\EmailContentParser::parse($question->title, $surveyParserData, true, true);
        $questionTitleLines = \App\TextHelpers::splitByWordLength($q . '. ' . $questionTitle, intval($namesOffset / 9));
        $height = max(count($questionTitleLines) * 20, 30 * $numRoles + 10) + 60;

        array_push($diagramQuestions, (object)[
            'questionTitleLines' => $questionTitleLines,
            'height' => $height
        ]);

        $totalQuestionsHeight += $height;
        $q++;
    }

    $y = $topOffset;
?>

<div id="{{ $diagramName }}Box">
    <svg id="{{ $diagramName }}" style="overflow: hidden;"
         height="{{ $totalQuestionsHeight + $topOffset + 50 + $scaleSpacing }}" width="{{ $fullWidth + 100 }}">

         <!-- Legend -->
        <g>
            <?php $currentY = 0; ?>
            @foreach($legend as $item)
                <rect fill="{{ $item->color }}" stroke-width="1" stroke="{{ $item->strokeColor }}"
                      height="{{ $legendBlockSize }}" width="{{ $legendBlockSize }}"
                      y="{{ $currentY }}" x="{{ $offsetX + $chartWidth + 40 + $legendSpacingX }}" />

                <text fill="#222222" stroke-width="0" stroke="none"
                      font-size="20" font-family="Calibri" text-anchor="start"
                      y="{{ $currentY + 15 }}" x="{{ $offsetX + $chartWidth + 45 + 25 + $legendSpacingX }}">
                  {{ $item->name }}
                </text>
                <?php $currentY += $legendBlockSize + 5; ?>
            @endforeach
        </g>

        <!-- Scale lines -->
        <g>
            @for ($i = 0; $i <= $numValues; $i += $scaleTick)
                <rect fill="#8e8e8e" stroke-width="0" stroke="none"
                      height="{{ $totalQuestionsHeight + $scaleSpacing + $markerHeight / 2 }}" width="{{ $i == 0 ? 2 : 1 }}"
                      y="{{ $topOffset }}" x="{{ $offsetX + $deltaBar * $i }}"></rect>
            @endfor
        </g>

        <!-- Questions -->
        <?php $q = 0; ?>
        @foreach ($allQuestionsRoles as $question)
            <?php
                $currentQuestion = $diagramQuestions[$q];
                $questionTitleLines = $currentQuestion->questionTitleLines;

                $questionValues = array_map(function ($role) use ($chartWidth, $selfRoleId) {
                    $value = 0;
                    $maxWidth = 0;

                    foreach ($role->data as $dataPoint) {
                        $maxWidth = max($maxWidth, $dataPoint->average);
                    }

                    $values = array_map(function ($value) use ($chartWidth) {
                        return (object)[
                            'value' => $value->average,
                            'width' => $chartWidth * $value->average,
                            'survey' => $value->survey
                        ];
                    }, $role->data);

                    usort($values, function ($x, $y) {
                        if ($x->value > $y->value) {
                            return -1;
                        } else if ($x->value < $y->value) {
                            return 1;
                        } else {
                            return 0;
                        }
                    });

                    return (object)[
                        'id' => $role->id,
                        'name' => $role->name,
                        'values' => $values,
                        'width' =>  $chartWidth * $maxWidth
                    ];
                }, $question->roles);
            ?>

        	<g>
        		<g>
        			<g>
                        <?php $currentY = $y + 4; ?>
                        @foreach ($questionValues as $value)
                            @foreach ($value->values as $dataPoint)
                                <?php
                                    $roleColor = $roleColors[$value->id];

                                    if ($dataPoint->survey->compareAgainstSurvey != null) {
                                        $color = (object)[
                                            'color' => $roleColor->olderColor,
                                            'strokeColor' => $roleColor->olderStrokeColor,
                                        ];
                                    } else {
                                        $color = $roleColor;
                                    }
                                ?>
                                <rect fill="{{ $color->color }}" stroke-width="1" stroke="{{ $color->strokeColor }}"
                                      height="30" width="{{ $dataPoint->width - 1 }}"
                                      y="{{ $currentY }}" x="{{ $offsetX + 1 }}">
                                </rect>
                            @endforeach

                            <?php $currentY += 39; ?>
                        @endforeach
        			</g>
        		</g>
        		<g>
                    <text fill="#222222" y="{{ $y + 26.75  }}" stroke-width="0" stroke="none"
                          font-size="20" font-family="Calibri" text-anchor="start">
                        @for ($j = 0; $j < count($questionTitleLines); $j++)
                            <tspan dy="{{ $j > 0 ? 20 : 0 }}" x="0">{{ $questionTitleLines[$j] }}</tspan>
                        @endfor
                    </text>
        		</g>
        		<g>
                    <?php $currentY = $y + 26; ?>
                    @foreach ($questionValues as $value)
                        @foreach ($value->values as $dataPoint)
                            <?php
                                $roleColor = $roleColors[$value->id];
                                if ($dataPoint->survey->compareAgainstSurvey != null) {
                                    $textColor = $roleColor->olderTextColor;
                                } else {
                                    $textColor = $roleColor->textColor;
                                }

                                $textColor = '#000';
                            ?>

                            <text fill="{{ $textColor }}" stroke-width="0" stroke="none" text-rendering="geometricPrecision" font-size="20" font-family="Calibri"
                                  y="{{ $currentY }}"
                                  x="{{ $offsetX + $dataPoint->width + 1 }}"
                                  text-anchor="start">
                                {{ round($dataPoint->value * 100) }}
                            </text>
                        @endforeach
                        <?php $currentY += 39; ?>
                    @endforeach
        		</g>
        	</g>
            <?php
                $q++;
                $y += $currentQuestion->height;
            ?>
        @endforeach

        <!-- Scale line descriptions -->
        <?php
            $scaleBasePosY = $y + $scaleSpacing;

            $markerColor = "#F79646";
            $baseLineColor = "#6B4190";
        ?>
        @if (count($answerType->valueExplanations()) > 0)
            <g>
                <g>
                    <rect fill="{{ $baseLineColor }}" stroke-width="0" stroke="none" height="2" width="{{ $chartWidth }}"
                          y="{{ $scaleBasePosY + $markerHeight / 2 - 1 }}" x="{{ $namesOffset }}"></rect>
                    @for ($i = 0; $i <= $numValues; $i += 2)
                        <rect fill="{{ $markerColor }}" stroke-width="0" stroke="none" height="{{ $markerHeight }}" width="{{ $markerBarWidth }}"
                              y="{{ $scaleBasePosY }}"
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

                            <text fill="#000" y="{{ $scaleBasePosY + 20 + $markerHeight }}" stroke-width="0" stroke="none"
                                  font-size="18" font-weight="bold" font-family="Calibri" text-anchor="middle">
                                @for ($j = 0; $j < count($lines); $j++)
                                    <tspan dy="{{ $j > 0 ? 15 : 0 }}" x="{{ $x }}">{{ $lines[$j] }}</tspan>
                                @endfor
                            </text>
                        </g>
                        <?php $i++; ?>
                    @endforeach

                    @for ($i = 0; $i <= $numValues; $i += 10)
                        <text fill="#000" x="{{ $namesOffset + $deltaBar * $i - $markerBarWidth / 2 }}" y="{{ $scaleBasePosY + 30 }}" stroke-width="0" stroke="none"
                              font-size="16" font-weight="bold" font-family="Calibri" text-anchor="{{ $i == 0 ? 'end' : 'start' }}">
                              {{ $i * 10 }}%
                        </text>
                    @endfor
                </g>
            </g>
        @else
            <g>
                <g>
                    <rect fill="{{ $baseLineColor }}" stroke-width="0" stroke="none" height="2" width="{{ $chartWidth }}"
                          y="{{ $scaleBasePosY + $markerHeight / 2 - 1 }}" x="{{ $namesOffset }}"></rect>
                    @for ($i = 0; $i <= $numValues; $i += 1)
                        <rect fill="{{ $markerColor }}" stroke-width="0" stroke="none" height="{{ $markerHeight }}" width="{{ $markerBarWidth }}"
                              y="{{ $scaleBasePosY }}"
                              x="{{ $namesOffset + $deltaBar * $i - $markerBarWidth / 2 }}"></rect>

                        <text fill="#000" x="{{ $namesOffset + $deltaBar * $i - $markerBarWidth / 2 }}" y="{{ $scaleBasePosY + 30 }}" stroke-width="0" stroke="none"
                              font-size="16" font-weight="bold" font-family="Calibri" text-anchor="middle">
                              {{ $i }}
                        </text>
                    @endfor
                </g>
            </g>
        @endif
    </svg>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        var svg = document.getElementById('{{ $diagramName }}');
        var svgData = new XMLSerializer().serializeToString(svg);
        $("#{{ $diagramName }}Box").html("<img src='" + svgToPng(svgData) + "'>");
    });
</script>
