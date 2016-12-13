<div class="form-group">
    <label>{{ Lang::get('surveys.language') }}</label>
    <select class="form-control" style="max-width: 40%" id="{{ $langFieldName }}">
        @foreach ($languages as $id => $name)
            <option value="{{ $id }}">{{ $name }}</option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label>{{ Lang::get('surveys.type') }}</label>
    <select class="form-control" style="max-width: 40%" id="{{ $typeFieldName }}">
        @if (\App\SurveyTypes::canCreateIndividual(Auth::user()->allowedSurveyTypes) || Auth::user()->isAdmin)
            <option value="{{ \App\SurveyTypes::Individual }}">{{ Lang::get('surveys.individualType') }}</option>
        @endif

        @if (\App\SurveyTypes::canCreateGroup(Auth::user()->allowedSurveyTypes) || Auth::user()->isAdmin)
            <option value="{{ \App\SurveyTypes::Group }}">{{ Lang::get('surveys.groupType') }}</option>
        @endif

        @if (\App\SurveyTypes::canCreateProgress(Auth::user()->allowedSurveyTypes) || Auth::user()->isAdmin)
            <option value="{{ \App\SurveyTypes::Progress }}">{{ Lang::get('surveys.progressType') }}</option>
        @endif

        @if (\App\SurveyTypes::canCreateLTT(Auth::user()->allowedSurveyTypes) || Auth::user()->isAdmin)
            <option value="{{ \App\SurveyTypes::LTT }}">{{ Lang::get('surveys.lttType') }}</option>
        @endif

        @if (\App\SurveyTypes::canCreateNormal(Auth::user()->allowedSurveyTypes) || Auth::user()->isAdmin)
            <option value="{{ \App\SurveyTypes::Normal }}">{{ Lang::get('surveys.normalType') }}</option>
        @endif
    </select>
</div>
