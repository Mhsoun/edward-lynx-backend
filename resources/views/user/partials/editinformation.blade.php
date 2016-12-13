<div class="form-group">
    @include('help.emailText', [
        'showLabel' => true,
        'labelText' => $labelText,
        'labelFor' => $fieldName,
        'boxName' => $fieldName . 'HelpBox'
    ])
    @if (isset($small) && !$small)
        <textarea id="{{ $fieldName }}" name="{{ $fieldName }}" class="form-control" rows="8">{{
            \App\EmailContentParser::textarea($text)
        }}</textarea>
    @else
        <input type="text" id="{{ $fieldName }}" name="{{ $fieldName }}" class="form-control"
               value="{{ App\EmailContentParser::textarea($text) }}">
    @endif
</div>
