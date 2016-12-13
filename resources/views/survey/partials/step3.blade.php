<h2 id="step4InfoText">{{ Lang::get('surveys.step4InfoTextCategory') }}</h2>
<div id="selectCategoryBox">
    {{ Lang::get('surveys.selectCategoryDescription') }}
    <br>
    <button type="button" class="btn btn-primary" onclick="javascript:SurveyStep3.showResults()">
        {{ Lang::get('buttons.skip') }}
    </button>

    <h3>{{ Lang::get('surveys.selectCategory') }}</h3>
    <div class="form-group">
        <select id="selectCategory" class="form-control" style="max-width: 40%">
            @foreach ($categories as $category)
                <option value="{{ $category->id }}">{{ $category->title }}</option>
            @endforeach
        </select>
    </div>

    <button type="button" class="btn btn-default" onclick="javascript:SurveyStep3.selectCategory()">
        {{ Lang::get('buttons.select') }}
    </button>

    <h3>{{ Lang::get('surveys.createCategory') }}</h3>
    <div class="form-group">
        <label for="categoryTitle">{{ Lang::get('questions.categoryTitle') }}</label>
        <input id="categoryTitle" type="text" class="form-control"
               placeholder="{{ Lang::get('questions.categoryTitlePlaceholder') }}" style="max-width: 40%">
    </div>
    <div class="form-group">
        <label for="categoryDescription">{{ Lang::get('questions.categoryDescription') }}</label>
        <textarea name="categoryDescription" id="categoryDescription"
                  class="form-control"
                  placeholder="{{ Lang::get('questions.categoryDescriptionPlaceholder') }}"
                  rows="5" style="max-width: 40%"></textarea>
    </div>

    <button type="button" class="btn btn-default" onclick="javascript:SurveyStep3.createCategory()">
        {{ Lang::get('buttons.create') }}
    </button>
</div>

<div id="selectQuestionsBox" style="display: none">
    <h3 style="display: inline;">
        <span id="selectedCategoryTitle">N/A</span>
    </h3>
    <span>
        <a class="textButton" href="javascript:SurveyStep3.toggleEditCategory()"><span class="glyphicon glyphicon-pencil"></span></a>
        <a class="textButton" href="javascript:SurveyStep3.deleteCategory()"><span class="glyphicon glyphicon-trash"></span></a>
    </span>
    <p id="selectedCategoryDescription"></p>
    <span id="editCategoryBox" style="display: none">
        <label>{{ Lang::get('surveys.categoryTitle') }}</label>
        <input type="text" id="editCategoryTitle" class="form-control" style="max-width: 40%">

        <label>{{ Lang::get('surveys.categoryDescription') }}</label>
        <textarea id="editCategoryDescription" class="form-control" rows="5" style="max-width: 40%"></textarea>

        <br>

        <button type="button" class="btn btn-success" onclick="SurveyStep3.saveCategory()">
            {{ Lang::get('buttons.save') }}
        </button>

        <button type="button" class="btn btn-danger" onclick="SurveyStep3.restoreCategory()">
            {{ Lang::get('buttons.discardChanges') }}
        </button>

        <br>
    </span>
    <label>{{ Lang::get('questions.parentCategory') }}</label>
    <select class="form-control" style="max-width: 40%" id="selectParentCategory">
    </select>

    <h4>{{ Lang::get('surveys.createQuestion') }}</h4>
    @include('survey.partials.createQuestion')

    <button type="button" class="btn btn-default" onclick="javascript:SurveyStep3.createQuestion()">
        {{ Lang::get('buttons.create') }}
    </button>

    <h4>{{ Lang::get('surveys.selectQuestions') }}</h4>
    <table id="selectedQuestionsTable" class="table">
        <col>
        <col width="45%">
        <tr class="tableHeader">
            <th><a href="javascript:SurveyStep3.selectAllQuestions()">{{ Lang::get('surveys.include') }}</a></th>
            <th>{{ Lang::get('surveys.question') }}</th>
            <th>{{ Lang::get('surveys.tags') }}</th>
            <th>{{ Lang::get('questions.questionScale') }}</th>
            <th>{{ Lang::get('questions.optional') }}</th>
            <th>{{ Lang::get('answertypes.nA') }}</th>
            <th id="questionTargetGroupCol">{{ Lang::get('surveys.targetGroup') }}</th>
            <th></th>
            <th></th>
        </tr>
    </table>

    <div class="pull-right">
        <button type="button" class="btn btn-warning btn-lg" onclick="javascript:SurveyStep3.showSelectCategory()">{{ Lang::get('buttons.back') }}</button>
        <button type="button" class="btn btn-primary btn-lg" onclick="javascript:SurveyStep3.showSelectCategory()">{{ Lang::get('surveys.anotherCategory') }}</button>
        <button type="button" class="btn btn-success btn-lg" onclick="javascript:SurveyStep3.showResults()">{{ Lang::get('buttons.save') }}</button>
    </div>
</div>

<div id="resultsBox" style="display: none">
    <p>{!! Lang::get('surveys.resultsText') !!}</p>
    <a href="javascript:SurveyStep3.showSelectCategory()" class="btn btn-primary" style="margin-bottom: 10px">{{ Lang::get('surveys.anotherCategory') }}</a>
    <div id="resultsContainer"></div>
    <button id="step3NextBtn" class="btn btn-primary nextBtn btn-lg pull-right" type="button">
        {{ Lang::get('buttons.next') }}
    </button>
</div>

<script type="text/javascript" src="{{ asset('js/survey.step3.js') }}"></script>
<script type="text/javascript">
    @foreach (\App\AnswerType::answerTypes() as $answerType)
        SurveyStep3.answerTypes.push({
            id: {{ $answerType->id() }},
            descriptionText: {!! json_encode($answerType->descriptionText()) !!},
            isText: {!! json_encode($answerType->isText()) !!}
        });
    @endforeach

    var questions = null;

    @foreach ($categories as $category)
        @if (!$category->isSurvey)
            questions = [];
            @foreach ($category->questions as $question)
                @if (!$question->isSurvey)
                    questions.push({
                        id: {{ $question->id }},
                        text: {!! json_encode($question->text) !!},
                        answerType: {{ $question->answerType }},
                        optional: {{ $question->optional }},
                        isNA: {{ $question->isNA }},
                        tags: {!! json_encode($question->tagsList()) !!}
                    });
                @endif
            @endforeach

            SurveyStep3.addCategory(
                {{ $category->id }},
                {!! json_encode($category->title) !!},
                {!! json_encode($category->description) !!},
                {!! json_encode($category->lang) !!},
                {{ $category->targetSurveyType !== null ? $category->targetSurveyType : -1 }},
                questions,
                {{ $category->parentCategoryId !== null ?  $category->parentCategoryId : -1 }});
        @endif
    @endforeach

    Survey.languageStrings["questions.deleteCategoryConfirmationText"] = "{!! Lang::get('questions.deleteCategoryConfirmationText') !!}";
    Survey.languageStrings["questions.deleteQuestionConfirmationText"] = "{!! Lang::get('questions.deleteQuestionConfirmationText') !!}";
    Survey.languageStrings["surveys.questionText"] = "{!! Lang::get('surveys.questionText') !!}";
    Survey.languageStrings["surveys.tags"] = "{!! Lang::get('surveys.tags') !!}";
    Survey.languageStrings["surveys.question"] = "{!! Lang::get('surveys.question') !!}";
    Survey.languageStrings["questions.questionOrder"] = "{!! Lang::get('questions.questionOrder') !!}";
    Survey.languageStrings["questions.questionScale"] = "{!! Lang::get('questions.questionScale') !!}";
    Survey.languageStrings["questions.optional"] = "{!! Lang::get('questions.optional') !!}";
    Survey.languageStrings["surveys.step4InfoTextCategory"] = "{!! Lang::get('surveys.step4InfoTextCategory') !!}";
    Survey.languageStrings["surveys.step4InfoTextCreateQuestions"] = "{!! Lang::get('surveys.step4InfoTextCreateQuestions') !!}";
    Survey.languageStrings["questions.noParentCategory"] = "{!! Lang::get('questions.noParentCategory') !!}";
    Survey.languageStrings["surveys.selectAll"] = "{!! Lang::get('surveys.selectAll') !!}";
    Survey.languageStrings["buttons.select"] = "{!! Lang::get('buttons.select') !!}";

    Survey.languageStrings["buttons.save"] = "{!! Lang::get('buttons.save') !!}";
    Survey.languageStrings["buttons.discardChanges"] = "{!! Lang::get('buttons.discardChanges') !!}";
</script>
