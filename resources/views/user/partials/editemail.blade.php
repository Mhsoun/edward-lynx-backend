<?php
    if (!isset($hideHelpBox)) {
        $hideHelpBox = false;
    }
?>

@if ($emailHeader != "")
    <h4>{{ $emailHeader }}</h4>
@endif

<div class="form-group">
    <label for="{{ $subjectFieldName }}">{{ Lang::get('surveys.emailSubject') }}</label>
    <input type="{{ $subjectFieldName }}" name="{{ $subjectFieldName }}" class="form-control"
           value="{{ $email->subject }}"
           style="">
</div>

<div class="form-group">
    @if (!$hideHelpBox)
        @include('help.emailText', [
            'showLabel' => true,
            'labelText' => Lang::get('surveys.emailText'),
            'labelFor' => $textFieldName,
            'boxName' => $textFieldName . 'HelpBox'
        ])
    @else
        <label>{{ Lang::get('surveys.emailText') }}</label>
    @endif
    <textarea id="{{ $textFieldName }}" name="{{ $textFieldName }}" class="form-control" rows="8" style="">{{
        \App\EmailContentParser::textarea($email->message)
    }}</textarea>
</div>
