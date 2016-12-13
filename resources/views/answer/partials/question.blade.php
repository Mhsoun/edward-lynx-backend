<?php
    $answerType = $question->answerType;
    $oldValue = old('answer_' . $question->id);
    $hasError = $errors->has('answer_' . $question->id) && !$answerType->isText() && $oldValue == '';

    if (isset($autoAnswer) && $autoAnswer) {
        if (!$answerType->isText()) {
            $oldValue = $answerType->values()[rand(0, count($answerType->values()) - 1)]->value;
        } else {
            $oldValue = str_random(20);
        }
    }

    $followUpQuestions = [];
    foreach ($question->followUpQuestions as $followUpQuestion) {
        $followUpQuestions[$followUpQuestion->followUpValue] = $followUpQuestion->id;
    }

    $attributes = [];
    if ($answerType->isText()) {
        array_push($attributes, 'textQuestion');
    }

    if ($hasError) {
        array_push($attributes, 'alert');
        array_push($attributes, 'alert-danger');
    }

    if (!$question->optional) {
        array_push($attributes, 'required');
    }

    if ($question->isFollowUpQuestion) {
        array_push($attributes, 'followUpQuestion');
    }
?>

<tr id="question_{{ $question->id }}"
    class="question {{ implode(' ', $attributes) }}"
    style="{{ $question->isFollowUpQuestion ? 'display: none' : '' }}"
    data-question-id="{{ $question->id }}">
    <td>
        <b>
            {!! \App\EmailContentParser::parse($question->text, $parserData, true) !!}
        </b>

        @if (!$answerType->isText())
            <table class="answerChoicesTables">
                <tr>
                    @foreach ($answerType->values() as $value)
                        <td>
                            @include('answer.partials.answerRadioButton', [
                                'oldValue' => $oldValue,
                                'value' => $value->value,
                                'questionId' => $question->id,
                                'followUpQuestions' => $followUpQuestions
                            ])
                        </td>
                    @endforeach
                    @if ($question->isNA)
                        <td style="width: 40px;"></td>
                        <td>
                            @include('answer.partials.answerRadioButton', [
                                'oldValue' => $oldValue,
                                'value' => \App\AnswerType::NA_VALUE,
                                'questionId' => $question->id,
                                'followUpQuestions' => $followUpQuestions
                            ])
                        </td>
                    @endif
                </tr>
                <tr>
                    @foreach ($answerType->values() as $value)
                        <td>{{ $value->description }}</td>
                    @endforeach
                    @if ($question->isNA)
                        <td></td>
                        <td>{{ Lang::get('answertypes.nA') }}</td>
                    @endif
                </tr>
            </table>
        @else
            <textarea class="form-control textAnswer"
                      style="width: 30em; margin-top: 5px" rows="6"
                      name="answer_{{ $question->id }}"
                      placeholder="{{ Lang::get('surveys.answerWithText') }}"
                      type="text"
                      autocomplete="off">{{ $oldValue }}</textarea>
        @endif
    </td>
</tr>
