@extends('layouts.default')
@section('content')
    <div class="mainBox">
        <h2>{{ Lang::get('reportTemplates.createReportTemplate') }} - {{ \App\SurveyTypes::name($surveyType) }}</h2>

        {!! Form::open(['action' => 'ReportTemplateController@store']) !!}
            <div class="form-group">
                <label>{{ Lang::get('reportTemplates.name') }}</label>
                <input type="text" class="form-control" name="name" style="max-width: 40%">
            </div>

            <input type="hidden" name="surveyId" value="{{ $surveyId }}">
            <input type="hidden" name="lang" value="{{ $lang }}">
            <input type="hidden" name="surveyType" value="{{ $surveyType }}">

            <?php
                $pages = [];

                if ($surveyType == \App\SurveyTypes::Individual) {
                    $pages = \App\Models\ReportTemplate::individualPageOrders();
                } else if (\App\SurveyTypes::isGroupLike($surveyType)) {
                    $pages = \App\Models\ReportTemplate::groupPageOrders();
                }
            ?>

    		@if (count($pages) > 0)
                <h3>{{ Lang::get('reportTemplates.pageOrders') }}</h3>
    			@include('reportTemplate.pageOrders', ['pages' => $pages])
    			<br>
            @endif

            <h3>{{ Lang::get('reportTemplates.diagramsTexts') }}</h3>
            <div id="reportTexts" class="row">
                @foreach (\App\Models\DefaultText::defaultReportTextsFor(Auth::user(), $surveyType)->reportTexts as $defaultText)
                    <?php $getText = $defaultText->getText; ?>
                    <?php $text = $getText($surveyType, $lang); ?>

                    <div class="col-md-5">
                        @if (isset($text->subject))
                            <h4>{{ $defaultText->header }}</h4>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" checked name="diagram_{{ $text->type }}_include" value="yes" autocomplete="off">
                                    {{ Lang::get('reportTemplates.includeInReport') }}
                                </label>
                            </div>

                            @include('user.partials.editemail', [
                                'emailHeader' => '',
                                'subjectFieldName' => 'diagram_' . $text->type . '_title',
                                'textFieldName' => 'diagram_' . $text->type . '_text',
                                'email' => $text,
                                'hideHelpBox' => true])
                        @else
                            @include('user.partials.editinformation', [
                                'labelText' => $defaultText->header,
                                'fieldName' => 'text_' . $text->type,
                                'text' => $text->text,
                                'small' => isset($defaultText->small) ? $defaultText->small : false])
                        @endif
                    </div>
                @endforeach
            </div>

            <button type="submit" class="btn btn-primary btn-lg">{{ Lang::get('buttons.create') }}</button>
        {!! Form::close() !!}
    </div>
@endsection
