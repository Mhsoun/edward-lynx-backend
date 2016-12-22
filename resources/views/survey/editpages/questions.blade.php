<div class="tab-pane" id="questions">
    <div>
        {!! Form::open(['action' => ['SurveyUpdateController@updateAddCategory', $survey->id], 'method' => 'put']) !!}
            <h2>{{ Lang::get('surveys.createCategory') }}</h2>
            <div class="form-group">
                <label for="categoryTitle">{{ Lang::get('questions.categoryTitle') }}</label>
                <input id="categoryTitle" name="categoryTitle" type="text" class="form-control"
                       placeholder="{{ Lang::get('questions.categoryTitlePlaceholder') }}" style="max-width: 40%">
            </div>
            <div class="form-group">
                <label for="categoryDescription">{{ Lang::get('questions.categoryDescription') }}</label>
                <textarea name="categoryDescription" id="categoryDescription"
                          class="form-control"
                          placeholder="{{ Lang::get('questions.categoryDescriptionPlaceholder') }}"
                          rows="5" style="max-width: 40%"></textarea>
            </div>
            <button type="submit" class="btn btn-default">{{ Lang::get('buttons.create') }}</button>
        {!! Form::close() !!}

        <?php
            $categories = \App\Models\QuestionCategory::
                where('ownerId', '=', $survey->ownerId)
                ->where('lang', '=', $survey->lang)
                ->where('isSurvey', '=', false)
                ->whereRaw('((targetSurveyType IS NULL) OR (targetSurveyType=?))', [$survey->type])
                ->get();
        ?>
        {!! Form::open(['action' => ['SurveyUpdateController@updateAddExistingCategory', $survey->id], 'method' => 'put']) !!}
            <h2>{{ Lang::get('surveys.selectCategoryOnly') }}</h2>
            <div class="form-group">
                <div class="form-group">
                    <select id="existingCategoryId" name="existingCategoryId" class="form-control" style="max-width: 40%">
                        @foreach ($categories as $category)
                            <?php
                                $inSurvey = $survey->categories()
                                    ->where('categoryId', '=', $category->id)
                                    ->count() > 0;

                                $sameTitleUsed = $survey->categories()
                                    ->whereRaw('categoryId IN (SELECT question_categories.id FROM question_categories WHERE question_categories.title=?)',
                                               [$category->title])
                                    ->count() > 0;

                                $isValid = !$inSurvey && !$sameTitleUsed;
                            ?>
                            @if ($isValid)
                                <option value="{{ $category->id }}">{{ $category->title }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-default">{{ Lang::get('buttons.add') }}</button>
        {!! Form::close() !!}

        {!! Form::open(['action' => ['SurveyUpdateController@updateAddQuestion', $survey->id], 'method' => 'put']) !!}
            <h2>{{ Lang::get('surveys.createQuestion') }}</h2>
            <div class="form-group">
                <label>{{ Lang::get('questions.questionCategory') }}</label>
                <div class="form-group">
                    <select id="categoryId" name="categoryId" class="form-control" style="max-width: 40%">
                        @foreach ($survey->categories as $category)
                            <option value="{{ $category->categoryId }}">{{ $category->category->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @include('survey.partials.createQuestion')

            <button type="submit" class="btn btn-default">{{ Lang::get('buttons.create') }}</button>
        {!! Form::close() !!}
    </div>

    <h2>{{ Lang::get('surveys.questions') }}</h2>
    <?php $categories = $survey->categoriesAndQuestions(); $numCategories = count($categories); ?>
    @foreach ($categories as $category)
        <h3 style="display: inline">{{ $category->title }}</h3>
        <a class="textButton" href="javascript:toggleEditCategory({{ $category->id }})"><span class="glyphicon glyphicon-pencil"></span></a>
        <div style="margin-bottom: 10px; display: none" id="editCategory_{{ $category->id }}">
            {!! Form::open(['action' => ['SurveyUpdateController@updateChangeCategoryTitle', $survey->id], 'method' => 'put']) !!}
                    <div class="form-group">
                        <input name="updateCategoryTitle" type="text" class="form-control" value="{{ $category->title }}" style="max-width: 40%">
                        <input name="updateCategoryId" type="hidden" value="{{ $category->id }}">
                    </div>
                    <input type="submit" class="btn btn-primary" value="{{ Lang::get('buttons.update') }}">
            {!! Form::close() !!}
        </div>
        <br>

        <b>{{ Lang::get('questions.order') }}: </b>
        <select class="form-control" autocomplete="off" onchange="updateCategoryOrder(this, {{ $category->id }})" style="display: inline; max-width: 10%">
            @for ($order = 0; $order < $numCategories; $order++)
                <option value="{{ $order }}" {{ $order == $category->order ? 'selected' : '' }}>
                    {{ $order + 1 }}
                </option>
            @endfor
        </select>
        <br>
        <br>
        <table class="table">
            <tr>
                <th>{{ Lang::get('questions.order') }}</th>
                <th>{{ Lang::get('surveys.question') }}</th>
                <th>{{ Lang::get('questions.questionScale') }}</th>
                <th>{{ Lang::get('surveys.tags') }}</th>
                <th>{{ Lang::get('questions.optional') }}</th>
                <th>{{ Lang::get('answertypes.nA') }}</th>
                <th></th>
                <th></th>
            </tr>
            <col style="width: 8em">
            <col style="width: 25em">
            <?php $numQuestions = count($category->questions); ?>
            @foreach ($category->questions as $question)
                <?php
                    $answerType = $question->answerType;
                    $customValues = \App\Models\Question::find($question->id)->customValues;
                ?>

                <tr>
                    <td>
                        <select class="form-control" autocomplete="off" onchange="updateQuestionOrder(this, {{ $question->id }})">
                            @for ($order = 0; $order < $numQuestions; $order++)
                                <option value="{{ $order }}" {{ $order == $question->order ? 'selected' : '' }}>
                                    {{ $order + 1 }}
                                </option>
                            @endfor
                        </select>
                    </td>
                    <td>
                        <span class="questionText">{{ $question->text }}</span>
                        <div class="editQuestion" style="display: none;">
                            <textarea class='editQuestionText form-control' cols='50' rows='5'>{{ $question->text }}</textarea>

                            @if ($answerType->id() == \App\AnswerType::CUSTOM_SCALE_TYPE)
                                <h4>{{ Lang::get('surveys.customScaleValues') }}</h4>
                                @foreach ($customValues as $value)
                                    <input type="text" class="form-control customScaleValue" style="margin-bottom: 10px"
                                           data-custom-scale-value-id="{{ $value->id }}"
                                           value="{{ $value->name }}">
                                @endforeach
                            @endif

                            <button type='button' class='btn btn-success' onclick="saveQuestion(this, {{ $question->id }})">
                                {{ Lang::get('buttons.save') }}
                            </button>
                        </div>
                    </td>
                    @if ($answerType->id() == \App\AnswerType::CUSTOM_SCALE_TYPE)
                        <td>
                            {{ $answerType->descriptionText() }}:
                            <select class="form-control customScaleValues" style="display: inline; width: 13em">
                                @foreach ($customValues as $value)
                                    <option value="{{ $value->id }}">{{ $value->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    @else
                        <td>{{ $answerType->descriptionText() }}</td>
                    @endif
                    <td>{{ implode(";", $question->tags->all()) }}</td>
                    <td>
                        <input type="checkbox" onchange="updateQuestionOptional(this, {{ $question->id }})" {{ $question->optional ? 'checked' : ''  }}>
                    </td>
                    <td>
                        <input type="checkbox" onchange="updateQuestionisNA(this, {{ $question->id }})" {{ $question->isNA ? 'checked' : ''  }}>
                    </td>
                    <td><a class='textButton' onclick="toggleEditQuestion(this)"><span class='glyphicon glyphicon-pencil'></span></a></td>
                    <td>
                        <a class='textButton' onclick="deleteQuestion(this, {{ $survey->id }}, {{ $question->id }})">
                            <span class='glyphicon glyphicon-trash'></span>
                        </a>
                    </td>
                </tr>
            @endforeach
        </table>
    @endforeach
</div>

<script type="text/javascript">
    function toggleEditCategory(categoryId) {
        $("#editCategory_" + categoryId).toggle();
    }

    //Toggles the edit for the given question
    function toggleEditQuestion(element) {
        element = $(element);
        var parent = element.parent().parent();
        parent.find(".editQuestion").toggle();
        parent.find(".questionText").toggle();
    }

    //Saves the changes to the given question
    function saveQuestion(element, questionId) {
        element = $(element);
        var parent = element.parent().parent().parent();
        var newText = parent.find(".editQuestionText").val();
        var customValues = [];

        parent.find(".customScaleValue").each(function (i, element) {
            element = $(element);
            customValues.push({
                id: element.data('custom-scale-value-id'),
                name: element.val()
            });
        });

        $.ajax({
            url: "/survey/question",
            method: "put",
            data: {
                questionId: questionId,
                questionText: newText,
                customValues: customValues
            },
            dataType: "json"
        }).done(function(data) {
            if (data.success) {
                parent.find(".questionText").text(newText);
                parent.find(".editQuestion").toggle();
                parent.find(".questionText").toggle();

                var customScaleValues = parent.find(".customScaleValues");
                customValues.forEach(function (customValue) {
                    customScaleValues.find("option[value=\"" + customValue.id + "\"]").text(customValue.name);
                });
            }
        });
    }

    //Updates the optional status for the given question
    function updateQuestionOptional(element, questionId) {
        element = $(element);

        $.ajax({
            url: "/survey/question/",
            method: "put",
            data: {
                questionId: questionId,
                optional: element.is(':checked')
            },
            dataType: "json"
        });
    }

    //Updates the NA status for the given question
    function updateQuestionIsNA(element, questionId) {
        element = $(element);

        $.ajax({
            url: "/survey/question/",
            method: "put",
            data: {
                questionId: questionId,
                isNA: element.is(':checked')
            },
            dataType: "json"
        });
    }

    //Updates the order for the given question
    function updateQuestionOrder(element, questionId) {
        element = $(element);

        $.ajax({
            url: "/survey/{{ $survey->id }}/update-changequestionorder",
            method: "put",
            data: {
                questionId: questionId,
                order: element.val()
            },
            dataType: "json"
        });
    }

    //Updates the order for the given category
    function updateCategoryOrder(element, categoryId) {
        element = $(element);

        $.ajax({
            url: "/survey/{{ $survey->id }}/update-changecategoryorder",
            method: "put",
            data: {
                categoryId: categoryId,
                order: element.val()
            },
            dataType: "json"
        });
    }

    //Deletes the given question
    function deleteQuestion(element, surveyId, questionId) {
        if (confirm({!! json_encode(Lang::get('questions.deleteQuestionConfirmationText')) !!})) {
            element = $(element);

            $.ajax({
                url: "/survey/" + surveyId + "/update-delete-question",
                method: "delete",
                data: {
                    questionId: questionId
                },
                dataType: "json"
            }).done(function(data) {
                if (data.success) {
                    element.parent().parent().remove();
                }
            });
        }
    }
</script>
