<?php
    if (!isset($emailText)) {
        $emailText = (object)[
            'subject' => '',
            'text' => ''
        ];
    }

    if (!isset($useOld)) {
        $useOld = false;
    }

    if ($useOld) {
        $oldSubject = old($name . 'Subject');
        $oldText = old($name . 'Text');

        if ($oldSubject !== "" && $oldSubject !== null) {
            $emailText->subject = $oldSubject;
        }

        if ($oldText !== "" && $oldText !== null) {
            $emailText->text = $oldText;
        }
    }
?>

@if ($header != "")
    <h4>{{ $header }}</h4>
@endif

<div class="form-group">
    <label for="{{ $name }}Subject">{{ Lang::get('surveys.emailSubject') }}</label>
    <input id="{{ $name }}Subject" name="{{ $name }}Subject" class="form-control"
           value="{{ $emailText->subject }}"
           style="max-width: 40%">
</div>

<div class="form-group">
    @include('help.emailText', [
        'showLabel' => true,
        'labelText' => Lang::get('surveys.emailText'),
        'labelFor' => $name . 'Text',
        'boxName' => $name . 'TextHelpBox'
    ])

    <textarea id="{{ $name }}Text" name="{{ $name }}Text" class="form-control" rows="8" style="max-width: 40%">{{
        \App\EmailContentParser::textarea($emailText->text)
    }}</textarea>
</div>
