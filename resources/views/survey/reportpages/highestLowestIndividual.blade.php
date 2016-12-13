<?php
    $highestLowestQuestions = [];
    $highestLowestRoles = [];

    if ($isGroupReport) {
        //If group, show manager & others combined.
        $managerRole = null;
        $selfRole = null;

        foreach ($questionsByRole as $role) {
            if ($role->id == $managerRoleId) {
                $managerRole = $role;
            } else if ($role->id == $selfRoleId) {
                $selfRole = $role;
            }
        }

        if ($managerRole != null) {
            array_push($highestLowestRoles, $managerRole);
        }

        array_push($highestLowestRoles, (object)[
            "id" => -1,
            "name" => $othersRoleName,
            "toEvaluate" => false,
            "questions" => $selfAndOthersQuestions->others
        ]);

        if ($selfRole != null) {
            array_push($highestLowestRoles, $selfRole);
        }
    } else {
        $highestLowestRoles = $questionsByRole;
    }

    //Counts the number of possible extracted questions
    function countPossibleExtractedQuestions($questions, $extractedQuestions, $highest)  {
        $count = 0;
        $numThresholdSelected = 0;

        //Decide what the threshold is, min for highest, max for lowest
        $threshold = $highest ? 100 : 0;
        foreach ($extractedQuestions as $question) {
            if ($highest) {
                $threshold = min($question->others, $threshold);
            } else {
                $threshold = max($question->others, $threshold);
            }
        }

        foreach ($extractedQuestions as $question) {
            if ($question->others == $threshold) {
                $numThresholdSelected++;
            }
        }

        foreach ($questions as $question) {
            if ($highest) {
                $count += $question->others == $threshold ? 1 : 0;
            } else {
                $count += $question->others == $threshold ? 1 : 0;
            }
        }

        return (object)[
            'count' => $count,
            'threshold' => $threshold,
            'numThresholdSelected' => $numThresholdSelected
        ];
    }
?>

<?php $i = 0; ?>
@foreach ($highestLowestRoles as $role)
    @if (isValidRole($survey, $role))
        <?php
            $roleQuestions = (object)[
                'id' => $role->id,
                'name' => $role->name,
                'highest' => [],
                'lowest' => []
            ];

            $roleHighestLowestQuestions = [];
            $sum = 0.0;

            foreach ($role->questions as $question) {
                $selfQuestion = \App\SurveyReportHelpers::findQuestionById(
                    $highestLowestRoles[count($highestLowestRoles) - 1]->questions,
                    $question->id);

                if ($selfQuestion != null) {
                    array_push($roleHighestLowestQuestions, (object)[
                        'title' => $question->title,
                        'id' => $question->id,
                        'categoryId' => $question->categoryId,
                        'category' => $question->category,
                        'answerType' => $question->answerType,
                        'self' => round($selfQuestion->average * 100),
                        'others' => round($question->average * 100),
                        'order' => round($question->average * 100)
                    ]);
                }

                $sum += $question->average;
            }

            usort($roleHighestLowestQuestions, function($x, $y) {
                if ($x->order > $y->order) {
                    return 1;
                } else if ($x->order < $y->order) {
                    return -1;
                } else {
                    return 0;
                }
            });

            //Extract the highest & lowest
            // $roleQuestions->highest = extractQuestions(array_reverse($roleHighestLowestQuestions));
            // $roleQuestions->lowest = extractQuestions($roleHighestLowestQuestions);
            $roleQuestions->highest = extractQuestions2($roleHighestLowestQuestions, true);
            $roleQuestions->lowest = extractQuestions2($roleHighestLowestQuestions, false);
            $roleQuestions->possibleHighest = countPossibleExtractedQuestions($roleHighestLowestQuestions, $roleQuestions->highest, true);
            $roleQuestions->possibleLowest = countPossibleExtractedQuestions($roleHighestLowestQuestions, $roleQuestions->lowest, false);

            if (count($role->questions) > 0) {
                $roleQuestions->average = $sum / count($role->questions);
            } else {
                $roleQuestions->average = 0;
            }

            $roleQuestions->count = count($role->questions);

            //Sort first by value, than by category
            usort($roleQuestions->lowest, function($x, $y) {
                if ($x->order > $y->order) {
                    return 1;
                } else if ($x->order < $y->order) {
                    return -1;
                } else {
                    return $x->categoryId - $y->categoryId;
                }
            });

            usort($roleQuestions->highest, function($x, $y) {
                if ($x->order > $y->order) {
                    return -1;
                } else if ($x->order < $y->order) {
                    return 1;
                } else {
                    return $x->categoryId - $y->categoryId;
                }
            });

            $highestLowestQuestions[$role->id] = $roleQuestions;
        ?>

        <?php $j = 0; ?>
        @foreach ($roleQuestions->highest as $question)
            <script type="text/javascript">
                drawQuestionDiagram(
                    "barchart_highest_{{ $i }}_{{ $j }}",
                    {!! json_encode(getAnswerValues($question->id)) !!},
                    {!! json_encode($selfRoleName) !!},
                    {{ $question->self }},
                    {!! json_encode($role->id) !!},
                    {!! json_encode($role->name) !!},
                    {{ $question->others }});
            </script>
            <?php $j++; ?>
        @endforeach

        <?php $j = 0; ?>
        @foreach ($roleQuestions->lowest as $question)
            <script type="text/javascript">
                drawQuestionDiagram(
                    "barchart_lowest_{{ $i }}_{{ $j }}",
                    {!! json_encode(getAnswerValues($question->id)) !!},
                    {!! json_encode($selfRoleName) !!},
                    {{ $question->self }},
                    {!! json_encode($role->id) !!},
                    {!! json_encode($role->name) !!},
                    {{ $question->others }});
            </script>
            <?php $j++; ?>
        @endforeach

        <?php $i++; ?>
    @endif
@endforeach

<?php $i = 0; ?>
@foreach ($highestLowestRoles as $role)
    @if (isValidRole($survey, $role))
        <?php $roleQuestions = $highestLowestQuestions[$role->id]; ?>
        @section('diagramContent')
            <!-- Highest -->
            @section('diagramContent')
                <?php $j = 0; $prevCategory = -1; ?>
                @foreach ($roleQuestions->highest as $question)
                    <div class="description">
                        @if ($prevCategory != $question->categoryId)
                            <h4>{{ $question->category }}</h4>
                        @endif
                        <i><b>{{ \App\EmailContentParser::parse($question->title, $surveyParserData, true, true) }}</b></i>
                        <div id="barchart_highest_{{ $i }}_{{ $j }}" class="questionDiagram"></div>
                        <br>
                        <?php $j++; $prevCategory = $question->categoryId; ?>
                    </div>
                @endforeach
            @overwrite

            <?php
                $title = getReportTextForRole($survey, 'defaultHighestReportText', $role->id, $reportTemplate)->subject;
                $description = getReportTextForRole($survey, 'defaultHighestReportText', $role->id, $reportTemplate)->message;

                if ($description != "") {
                    $description = $description . "\n<br>\n";
                }

                $displayValue = $roleQuestions->possibleHighest->threshold . "%";
                if ($survey->type == \App\SurveyTypes::Individual
                    && count($roleQuestions->highest) > 0) {
                    $displayValue = getDisplayValue(
                        \App\AnswerType::forQuestion($roleQuestions->highest[0]->id),
                         $roleQuestions->possibleHighest->threshold);
                }

                if ($roleQuestions->possibleHighest->count != $roleQuestions->possibleHighest->numThresholdSelected) {
                    $description = $description . sprintf(
                        Lang::get('report.possibleQuestionsCount'),
                        $roleQuestions->possibleHighest->count,
                        $displayValue,
                        $roleQuestions->possibleHighest->numThresholdSelected);
                }
            ?>

            @include('survey.reportpages.diagram', [
                'diagramName' => 'highest' . $i,
                'titleText' => $title,
                'bodyText' => $description,
                'pageBreak' => true,
                'noIncludeBox' => true
            ])

            <!-- Lowest -->
            @section('diagramContent')
                <?php $j = 0; $prevCategory = -1; ?>
                @foreach ($roleQuestions->lowest as $question)
                    <div class="description">
                        @if ($prevCategory != $question->categoryId)
                            <h4>{{ $question->category }}</h4>
                        @endif
                        <i><b>{{ \App\EmailContentParser::parse($question->title, $surveyParserData, true, true) }}</b></i>
                        <div id="barchart_lowest_{{ $i }}_{{ $j }}" class="questionDiagram"></div>
                        <br>
                        <?php $j++; $prevCategory = $question->categoryId; ?>
                    </div>
                @endforeach
            @overwrite

            <?php
                $title = getReportTextForRole($survey, 'defaultLowestReportText', $role->id, $reportTemplate)->subject;
                $description = getReportTextForRole($survey, 'defaultLowestReportText', $role->id, $reportTemplate)->message;

                if ($description != "") {
                    $description = $description . "\n<br>\n";
                }

                $displayValue = $roleQuestions->possibleLowest->threshold . "%";
                if ($survey->type == \App\SurveyTypes::Individual
                   && count($roleQuestions->lowest) > 0) {
                    $displayValue = getDisplayValue(
                        \App\AnswerType::forQuestion($roleQuestions->lowest[0]->id),
                         $roleQuestions->possibleLowest->threshold);
                }

                if ($roleQuestions->possibleLowest->count != $roleQuestions->possibleLowest->numThresholdSelected) {
                    $description = $description . sprintf(
                        Lang::get('report.possibleQuestionsCount'),
                        $roleQuestions->possibleLowest->count,
                        $displayValue,
                        $roleQuestions->possibleLowest->numThresholdSelected);
                }
            ?>

            @include('survey.reportpages.diagram', [
                'diagramName' => 'lowest' . $i,
                'titleText' => $title,
                'bodyText' => $description,
                'pageBreak' => true,
                'noIncludeBox' => true
            ])
        @overwrite

        @include('survey.reportpages.diagram', [
            'diagramName' => 'highestLowest' . $i,
            'includeTitle' => Lang::get('report.highestLowestRoleInclude') . ($role->name != '' ? ' ' . $role->name : ''),
            'pageBreak' => false,
            'isPage' => true,
            'hasTitleAndText' => false
        ])
        <?php $i++; ?>
    @endif
@endforeach
