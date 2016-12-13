@if ($showLabel)
    <label for="{{ $labelFor }}">
        {{ $labelText }}
        <span class="textButton glyphicon glyphicon-info-sign"
              style="font-size: medium"
              title="{{ Lang::get('parserHelp.infoText') }}"
              onclick="$('#{{ $boxName }}').toggle()"></span>
    </label>
@endif

<div id="{{ $boxName }}" class="helpBox" style="display: none;">
    {!! Lang::get('answertypes.helpText') !!}
    <table class="table" style="margin-bottom: 0px;">
        <tr>
            <th>Scale</th>
            <th>Meaning</th>
        </tr>
        @foreach (\App\AnswerType::answerTypes() as $answerType)
            <tr>
                <td>{{ $answerType->descriptionText() }}</td>
                <td>{!! $answerType->helpText() !!}</td>
            </tr>
        @endforeach
    </table>
</div>
