@extends('layouts.default')

@section('content')
    <div class="mainBox">
        @if (\App\SurveyTypes::isIndividualLike($survey->type))
            <h2>
                {{ Lang::get('surveys.answersFor') }}: {{ $recipient->recipient->name }} {{ Lang::get('surveys.evaluating') }} {{ $recipient->invitedByObj->name }}
            </h2>
        @else
            <h2>{{ Lang::get('surveys.answersFor') }}: {{ $recipient->recipient->name }}</h2>
        @endif

        <button class="btn btn-primary" type="button" style="margin-bottom: 12px"
                onclick="removeAnswers({{ $recipient->invitedById }}, {{ $recipient->recipientId }})">{{ Lang::get('surveys.removeAnswers') }}</button>

        <table class="table">
            <col style="width: 20%">
            <col style="width: 40%">
            <col style="width: 20%">
            <col style="width: 20%">
            <tr>
                <th>{{ Lang::get('questions.questionCategory') }}</th>
                <th>{{ Lang::get('questions.questionHeader') }}</th>
                <th>{{ Lang::get('questions.questionScale') }}</th>
                <th>{{ Lang::get('questions.questionAnswer') }}</th>
            </tr>
            @foreach ($answers as $answer)
                <?php $answerType = $answer->question->answerTypeObject() ?>
                <tr>
                    <td>{{ $answer->question->category->title }}</td>
                    <td>{{ $answer->question->text }}</td>
                    <td>{{ $answerType->descriptionText() }}</td>
                    <td>
                        @if ($answerType->isText())
                            {{ $answer->answerText }}
                        @else
                            {{ $answerType->descriptionOfValue($answer->answerValue) }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>

        <?php
            $backUrl = action('SurveyController@show', ['id' => $survey->id]);
            if (\App\SurveyTypes::isIndividualLike($survey->type)) {
                $backUrl = action('SurveyController@showCandidate', ['id' => $survey->id, 'candidateId' => $recipient->invitedById]);
            } else if (\App\SurveyTypes::isGroupLike($survey->type)) {
                $backUrl = action('SurveyController@showRole', ['id' => $survey->id, 'roleId' => $recipient->roleId]);
            }
        ?>

        <a href="{{ $backUrl }}">{{ Lang::get('buttons.back') }}</a>
    </div>

    <script type="text/javascript">
        function removeAnswers(candidateId, recipientId) {
            if (confirm({!! json_encode(Lang::get('surveys.removeAnswersConfirmation')) !!})) {
                document.location = "{{ action('SurveyUpdateController@updateDeleteAnswers', ['id' => $survey->id]) }}" + "?candidateId=" + candidateId + "&recipientId=" + recipientId;
            }
        }
    </script>
@endsection
