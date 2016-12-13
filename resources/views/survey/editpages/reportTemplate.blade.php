<div class="tab-pane" id="reportTemplate">
    <h3>{{ Lang::get('surveys.selectReportTemplate') }}</h3>

    <?php
        $reportTemplates = \App\Models\ReportTemplate::
            where('ownerId', '=', Auth::user()->id)
            ->where('surveyType', '=', $survey->type)
            ->get();
    ?>

    {!! Form::open(['method' => 'put', 'action' => ['SurveyUpdateController@updateSetReportTemplate', 'id' => $survey->id]]) !!}
        <div class="form-group">
            <select class="form-control" style="max-width: 40%" name="activeReportTemplateId" autocomplete="off">
                <option value="">{{ Lang::get('surveys.noTemplate') }}</option>
                @foreach ($reportTemplates as $reportTemplate)
                    @if ($reportTemplate->id === $survey->activeReportTemplateId)
                        <option selected value="{{ $reportTemplate->id }}">{{ $reportTemplate->name }}</option>
                    @else
                        <option value="{{ $reportTemplate->id }}">{{ $reportTemplate->name }}</option>
                    @endif
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">{{ Lang::get('buttons.select') }}</button>
    {!! Form::close() !!}

    <?php
        $createUrl = action('ReportTemplateController@create', [
            'surveyType' => $survey->type,
            'lang' => $survey->lang,
            'surveyId' => $survey->id,
        ]);
    ?>

    <h3>{{ Lang::get('surveys.createReportTemplate') }}</h3>
    {{ Lang::get('surveys.createReportTemplateText') }}, <a href="{{ $createUrl }}">{{ Lang::get('buttons.clickHere') }}</a>.
</div>
