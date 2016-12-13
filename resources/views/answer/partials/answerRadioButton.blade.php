<?php
    if (array_key_exists($value, $followUpQuestions)) {
        $onClickStr = 'onclick="AnswerView.showFollowUp(' . $questionId . ', ' . $followUpQuestions[$value] . ')"';
    } else {
        $onClickStr = 'onclick="AnswerView.hideFollowUp(' . $questionId . ', ' . json_encode(array_values($followUpQuestions)) . ')"';
    }
?>

@if ($value === intval($oldValue) && $oldValue != null)
    <input type="radio"
           value="{{ $value }}"
           name="answer_{{ $questionId }}"
           checked="checked"
           autocomplete="off"
           {!! $onClickStr !!}>
@else
    <input type="radio"
           value="{{ $value }}"
           name="answer_{{ $questionId }}"
           autocomplete="off"
           {!! $onClickStr !!}>
@endif
