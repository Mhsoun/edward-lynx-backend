<?php
    if (!isset($nameField)) {
        $nameField = "name";
    }

    if (!isset($emailField)) {
        $emailField = "email";
    }

    if (!isset($positionField)) {
        $positionField = "position";
    }

    if (!isset($existingRecipientField)) {
        $existingRecipientField = "existingRecipientId";
    }

    if (!isset($isProgressCandidate)) {
        $isProgressCandidate = false;
    }

    if (!isset($is360Candidate)) {
        $is360Candidate = false;
    }
?>

<div>
    <h4>{{ $newRecipientHeader }}</h4>
    <div class="form-group">
        <label for="{{ $nameField }}">{{ Lang::get('surveys.recipientName') }}</label>
        <input type="text" class="form-control" id="{{ $nameField }}" name="{{ $nameField }}"
               placeholder="{{ Lang::get('surveys.recipientNamePlaceholder') }}" style="max-width: 40%">
    </div>

    <div class="form-group">
        <label for="{{ $emailField }}">{{ Lang::get('surveys.recipientEmail') }}</label>
        <input type="email" class="form-control" id="{{ $emailField }}" name="{{ $emailField }}"
               placeholder="{{ Lang::get('surveys.recipientEmailPlaceholder') }}" style="max-width: 40%">
    </div>

    @if (!isset($ignorePosition) || !$ignorePosition)
        <div class="form-group">
            <label for="{{ $positionField }}">{{ Lang::get('surveys.recipientPosition') }}</label>
            <input type="text" class="form-control" id="{{ $positionField }}" name="{{ $positionField }}"
                   placeholder="{{ Lang::get('surveys.recipientPositionPlaceholder') }}" style="max-width: 40%">
        </div>
    @endif

    @if ($isProgressCandidate)
        @include('shared.dateTimePicker', [
            'name' => 'newEndDate',
            'label' => Lang::get('surveys.endDate'),
        ])

        @include('shared.dateTimePicker', [
            'name' => 'newEndDateRecipients',
            'label' => Lang::get('surveys.endDate') . ' ' . Lang::get('surveys.participants'),
        ])
    @endif

    @if ($is360Candidate)
        @include('shared.dateTimePicker', [
            'name' => 'newEndDate',
            'label' => Lang::get('surveys.endDate'),
            'value' => $survey->endDate
        ])
    @endif

    <button type="submit" class="btn btn-primary" {!! isset($newRecipientOnClick) ? "onclick=\"$newRecipientOnClick\"" : "" !!}>
        {{ Lang::get('buttons.add') }}
    </button>
</div>

<div>
    <h4>{{ Lang::get('surveys.fromExisting') }}</h4>
    <div class="form-group">
        <select id="{{ $existingRecipientField }}" name="{{ $existingRecipientField }}" class="form-control" style="max-width: 40%">
            @foreach ($existingRecipients as $recipient)
                <option value="{{ $recipient->id }}">{{ $recipient->name }} ({{ $recipient->mail }})</option>
            @endforeach
        </select>
    </div>

    @if ($isProgressCandidate)
        @include('shared.dateTimePicker', [
            'name' => 'existingEndDate',
            'label' => Lang::get('surveys.endDate'),
        ])

        @include('shared.dateTimePicker', [
            'name' => 'existingEndDateRecipients',
            'label' => Lang::get('surveys.endDate') . ' ' . Lang::get('surveys.participants'),
        ])
    @endif

    @if ($is360Candidate)
        @include('shared.dateTimePicker', [
            'name' => 'existingEndDate',
            'label' => Lang::get('surveys.endDate'),
            'value' => $survey->endDate
        ])
    @endif

    <button type="submit" class="btn btn-primary" {!! isset($existingRecipientOnClick) ? "onclick=\"$existingRecipientOnClick\"" : "" !!}>
        {{ Lang::get('buttons.add') }}
    </button>
</div>
