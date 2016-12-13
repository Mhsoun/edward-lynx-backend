<div class="includeCheckbox checkbox">
    <label>
        <input type="checkbox" onclick="toggleShowInReport(this, 'selectedQuestionsPage')" checked="checked" autocomplete="off">
        {{ Lang::get('surveys.include') }} {{ Lang::get('report.selectedQuestions') }}
    </label>
</div>
<div id="selectedQuestionsPage" class="pageBreak">
    <h3 class="editTitle" id="selectedQuestionsTitle">{{ getReportText($survey, 'defaultSelectedQuestionReportText', $reportTemplate)->subject}}</h3>
    <a class="textButton editTextButton" onclick="showEditText('selectedQuestionsTitle', 'selectedQuestionsText', 'selectedQuestionsBox')">
        <span class="glyphicon glyphicon-pencil"></span>
    </a>
    <p id="selectedQuestionsText" class="description">{{ getReportText($survey, 'defaultSelectedQuestionReportText', $reportTemplate)->message }}</p>
    <div style="display: none" id="selectedQuestionsBox">
        <input type="textbox" class="editTitle form-control" style="max-width: 40%; margin-bottom: 5px">
        <textarea class="editText form-control" style="max-width: 40%" rows="5"></textarea>
        <br>
        <button class="btn btn-primary"
                onclick="saveEditText('selectedQuestionsTitle', 'selectedQuestionsText', 'selectedQuestionsBox')">
            {{ Lang::get('buttons.save') }}
        </button>
    </div>

    <div class="well well-lg previewOnly">
        <h2 style="margin-top: 0;">{{ Lang::get('report.selectQuestion') }}</h2>
        <div class="form-group">
            <label>{{ Lang::get('questions.questionCategory') }}</label>
            <select class="form-control" style="max-width: 40%" id="selectQuestionsCategory">
                @foreach ($selfAndOthersCategories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label>{{ Lang::get('questions.questionHeader') }}</label>
            <select class="form-control" style="max-width: 40%" id="selectQuestionsQuestion"></select>
        </div>

        <button type="button" class="btn btn-primary" id="selectQuestionsAddQuestion">{{ Lang::get('buttons.add') }}</button>
    </div>

    <div id="selectedQuestionDiagrams"></div>
</div>

<?php
    $selfAndOthersCategories = [];
    $answerTypes = [];

    foreach ($selfAndOthersCategories as $category) {
        $questions = [];

        foreach ($category->roles as $role) {
            foreach ($role->questions as $roleQuestion) {
                $question = [];
                if (array_key_exists($roleQuestion->id, $questions)) {
                    $question = $questions[$roleQuestion->id];
                }

                array_push($question, (object)[
                    'name' => $role->name,
                    'question' => (object)[
                        'id' => $roleQuestion->id,
                        'title' =>  \App\EmailContentParser::parse($roleQuestion->title, $surveyParserData, true, true),
                        'answerType' => $roleQuestion->answerType,
                        'average' => round($roleQuestion->average * 100)
                    ]
                ]);

                if (!array_key_exists($roleQuestion->answerType, $answerTypes)) {
                    $answerTypes[$roleQuestion->answerType] = getAnswerValues($roleQuestion->id);
                }

                $questions[$roleQuestion->id] = $question;
            }
        }

        $selfAndOthersCategories[$category->id] = (object)[
            'name' => $category->name,
            'questions' => $questions
        ];
    }
?>

<script type="text/javascript">
    $(document).ready(function() {
        var categories = {!! json_encode($selfAndOthersCategories) !!};
        var includedQuestions = {};
        var selectQuestionsQuestion = $("#selectQuestionsQuestion");
        var currentCategory = null;
        var selectedQuestionDiagrams = $("#selectedQuestionDiagrams");

        var answerTypes = {!! json_encode($answerTypes) !!};

        $("#selectQuestionsCategory").change(function(e) {
            currentCategory = categories[$(e.target).val()];

            selectQuestionsQuestion.find("option").remove();
            for (var id in currentCategory.questions) {
                var question = currentCategory.questions[id][0].question;
                if (includedQuestions[question.id] === undefined) {
                    selectQuestionsQuestion.append(jQuery("<option />").text(question.title).val(question.id));
                }
            }
        });

        $("#selectQuestionsCategory").change();

        $("#selectQuestionsAddQuestion").click(function() {
            if (currentCategory != null) {
                var questionId = selectQuestionsQuestion.val();

                if (questionId != null) {
                    var question = currentCategory.questions[questionId];
                    var questionData = question[0].question;

                    includedQuestions[questionData.id] = true;
                    $("#selectQuestionsCategory").change();

                    //Create the diagram
                    var questionDiv = jQuery("<div />");
                    selectedQuestionDiagrams.append(questionDiv);

                    var deleteButton = jQuery("<a class='textButton previewOnly'><span class='glyphicon glyphicon-remove' /></a>");
                    questionDiv.append(deleteButton);
                    deleteButton.click(function() {
                        delete includedQuestions[questionData.id];
                        questionDiv.remove();
                        $("#selectQuestionsCategory").change();
                    });

                    questionDiv.append(jQuery("<b />").text(currentCategory.name));
                    questionDiv.append(jQuery("<br>"));
                    questionDiv.append(jQuery("<i />").text(questionData.title));

                    var diagram = jQuery("<div class='questionDiagram' />");
                    questionDiv.append(diagram);
                    questionDiv.append(jQuery("<br>"));

                    drawDiagramWithQuestion(
                        diagram[0],
                        answerTypes[questionData.answerType],
                        question[0].name,
                        question[0].question.average,
                        -1,
                        question[1].name,
                        question[1].question.average);
                }
            }
        });
    });
</script>
