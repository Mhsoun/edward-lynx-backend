@if ($showLabel)
    <label for="{{ $labelFor }}">
        {{ $labelText }}  
        <span class="textButton glyphicon glyphicon-info-sign" style="font-size: medium"
              title="{{ Lang::get('parserHelp.infoText') }}"
              onclick="$('#{{ $boxName }}').toggle()"></span>
    </label>
@endif

<div id="{{ $boxName }}" class="helpBox" style="display: none;">
    {!! Lang::get('parserHelp.description') !!}
    <table class="table" style="margin-bottom: 0px;">
        <tr>
            <th>{{ Lang::get('parserHelp.wordHeader') }}</th>
            <th>{{ Lang::get('parserHelp.replacementHeader') }}</th>
        </tr>
        <tr>
            <td><pre>#recipient_name</pre></td>
            <td>{{ Lang::get('parserHelp.recipientNameDescription') }}</td>
        </tr>
        <tr>
            <td><pre>#to_evaluate_name</pre></td>
            <td>{{ Lang::get('parserHelp.toEvaluateNameDescription') }}</td>
        </tr>
        <tr>
            <td><pre>#to_evaluate_group_name</pre></td>
            <td>{{ Lang::get('parserHelp.toEvaluateGroupNameDescription') }}</td>
        </tr>
        <tr>
            <td><pre>#to_evaluate_role_name</pre></td>
            <td>{{ Lang::get('parserHelp.toEvaluateRoleNameDescription') }}</td>
        </tr>
        <tr>
            <td><pre>#survey_name</pre></td>
            <td>{{ Lang::get('parserHelp.surveyNameDescription') }}</td>
        </tr>
        @if ($includeLink)
            <tr>
                <td><pre>#link</pre></td>
                <td>{{ Lang::get('parserHelp.linkDescription') }}</td>
            </tr>
        @endif
        <tr>
            <td><pre>#end_date</pre></td>
            <td>{{ Lang::get('parserHelp.endDateDescription') }}</td>
        </tr>
        <tr>
            <td><pre>#company_name</pre></td>
            <td>{{ Lang::get('parserHelp.companyNameDescription') }}</td>
        </tr>
    </table>
</div>