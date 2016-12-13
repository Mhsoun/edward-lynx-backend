<div class="form-group">
    @include('help.scaleText', [
        'showLabel' => true,
        'labelFor' => 'questionScale',
        'labelText' => Lang::get('questions.questionScale'),
        'boxName' => 'scaleHelp'
    ])
    <select id="questionScale" name="questionScale" class="form-control" style="max-width: 40%">
        @foreach (\App\AnswerType::answerTypes() as $answerType)
            <option value="{{ $answerType->id() }}">{{ $answerType->descriptionText() }}</option>
        @endforeach
    </select>
</div>

<div class="form-group">
     <label>{{ Lang::get('questions.optional') }}</label>
     <div class="checkbox">
        <label>
            <input id="questionOptional" name="questionOptional" type="checkbox" value="yes"> {{ Lang::get('buttons.yes') }}
        </label>
    </div>
</div>

<div class="form-group">
     <label>{{ Lang::get('answertypes.nA') }}</label>
     <div class="checkbox">
        <label>
            <input id="questionIsNA" name="questionIsNA" type="checkbox" value="yes" checked> {{ Lang::get('buttons.yes') }}
        </label>
    </div>
</div>

<div id="customScaleBox" style="display: none">
    <h4>{{ Lang::get('surveys.customScaleValues') }}</h4>

    <input type="text" class="form-control" style="max-width: 20em; margin-bottom: 5px; display: inline" id="questionNewCustomValue">
	<a class="textButton"><span class="glyphicon glyphicon-plus" id="addQuestionNewCustomValue"></span></a>

    <ul id="questionCustomValuesList"></ul>
</div>

<div class="form-group">
    @include('help.descriptionText', [
        'showLabel' => true,
        'labelText' => Lang::get('questions.questionText'),
        'labelFor' => 'questionText',
        'boxName' => 'questionTextHelpBox'
    ])
    <textarea
        id="questionText"
        name="questionText"
        class="form-control"
        placeholder="{{ Lang::get('questions.questionTextPlaceholder') }}"
        rows="3"
        style="max-width: 40%"></textarea>
</div>

<div class="form-group">
    <label>{{ Lang::get('surveys.tags') }}</label>
    <input type="text" id="questionTags" name="questionTags" class="form-control"
           style="max-width: 40%" placeholder="{{ Lang::get('surveys.tagsPlaceholder') }}">
</div>

<script type="text/javascript">
    var questionNewCustomValue = $("#questionNewCustomValue");
    var questionCustomValuesList = $("#questionCustomValuesList");

    $("#questionScale").change(function (e) {
        if (e.target.value == {{ \App\AnswerType::CUSTOM_SCALE_TYPE }}) {
            $("#customScaleBox").show();
        } else {
            $("#customScaleBox").hide();
        }
    });
    $("#questionScale").change();

    $("#addQuestionNewCustomValue").click(function () {
        var value = questionNewCustomValue.val();
        if (value != "") {
            var newValueItem = $("<li />");
            var deleteButton = $("<a class='textButton'><span class='glyphicon glyphicon-trash' /></a>")
                .css("margin-right", "10px");

    		newValueItem.append(deleteButton)
            newValueItem.append($("<span />").text(value));
            newValueItem.append($("<input type='hidden' name='questionCustomValues[]'>").val(value));

    		deleteButton.click(function() {
    			newValueItem.remove();
    		});

            questionCustomValuesList.append(newValueItem);
            questionNewCustomValue.val("");
        }
    });

    function clearCustomValues() {
        questionCustomValuesList.find("li").remove();
    }
</script>
