<div class="includeCheckbox checkbox">
    <label>
        <input type="checkbox" onclick="toggleShowInReport(this, 'participantsPage')" checked="checked" autocomplete="off">
        {{ Lang::get('surveys.include') }} {{ Lang::get('surveys.participants') }}
    </label>
</div>
<div id="participantsPage" class="pageBreak">
    <h3 class="editTitle" id="participantsTitle">{{ getReportText($survey, 'defaultParticipantsReportText', $reportTemplate)->subject }}</h3>
    <a class="textButton editTextButton" onclick="showEditText('participantsTitle', 'participantsText', 'commentsBox')">
        <span class="glyphicon glyphicon-pencil"></span>
    </a>
    <p id="participantsText" class="description">{{ getReportText($survey, 'defaultParticipantsReportText', $reportTemplate)->message }}</p>
    <div style="display: none" id="commentsBox">
        <input type="textbox" class="editTitle form-control" style="max-width: 40%; margin-bottom: 5px">
        <textarea class="editText form-control" style="max-width: 40%" rows="5"></textarea>
        <br>
        <button class="btn btn-primary" onclick="saveEditText('participantsTitle', 'participantsText', 'commentsBox')">
            {{ Lang::get('buttons.save') }}
        </button>
    </div>

    <table class="table">
        <tr>
            <th>{{ Lang::get('surveys.recipientName') }}</th>
            <?php $surveyExtraQuestions = \App\ExtraAnswerValue::valuesForSurvey($survey); ?>
            @foreach ($surveyExtraQuestions as $extraQuestion)
                <th>{{ \App\EmailContentParser::parse($extraQuestion->label(), $surveyParserData, true) }}</th>
            @endforeach
        </tr>

        @foreach ($extraAnswersByRecipients as $recipient)
            <tr>
                <td>{{ $recipient->recipient->recipient->name }}</td>
                @foreach ($surveyExtraQuestions as $extraQuestion)
                    @if (array_key_exists($extraQuestion->id(), $recipient->answers))
                        <?php $answer = $recipient->answers[$extraQuestion->id()]; ?>
                        <td>{{ $answer->value }}</td>
                    @else
                        <td></td>
                    @endif
                @endforeach
            </tr>
        @endforeach
    </table>
</div>
