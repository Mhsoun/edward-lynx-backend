@extends('layouts.default')

@section('content')
    <div class="mainBox">
        <h4 style="text-align: center">{{ Lang::get('surveys.navigationText') }}</h4>
        <div class="stepwizard">
            <div class="stepwizard-row setup-panel">
                <div class="stepwizard-step">
                    <a href="#step-1" type="button" class="btn btn-primary btn-circle">1</a>
                    <p>{{ Lang::get('surveys.step1') }}</p>
                </div>
                <div class="stepwizard-step">
                    <a href="#step-2" type="button" class="btn btn-default btn-circle" disabled="disabled">2</a>
                    <p>{{ Lang::get('surveys.step2') }}</p>
                </div>
                <div class="stepwizard-step">
                    <a href="#step-3" type="button" class="btn btn-default btn-circle" disabled="disabled">3</a>
                    <p>{{ Lang::get('surveys.step3') }}</p>
                </div>
                <div class="stepwizard-step">
                    <a href="#step-4" type="button" class="btn btn-default btn-circle" disabled="disabled">4</a>
                   <p>{{ Lang::get('surveys.step4') }}</p>
                </div>
                <div class="stepwizard-step">
                    <a href="#step-5" type="button" class="btn btn-default btn-circle" disabled="disabled">5</a>
                    <p>{{ Lang::get('surveys.step5') }}</p>
                </div>
                <div class="stepwizard-step">
                    <a href="#step-6" type="button" class="btn btn-default btn-circle" disabled="disabled">6</a>
                    <p>{{ Lang::get('surveys.step6') }}</p>
                </div>
                <div class="stepwizard-step" id="step7NavButton" style="display: none">
                    <a href="#step-7" type="button" class="btn btn-default btn-circle" disabled="disabled">7</a>
                    <p>{{ Lang::get('surveys.step7') }}</p>
                </div>
            </div>
        </div>
        <div style="width: 100%">
            <a class="textButton" onclick="Survey.toggleSettings();">
                <span class="glyphicon glyphicon-cog" style="margin-bottom: 10px; font-size: 25px; text-align: center; display: block"></span>
            </a>

            <div id="settingsBox" style="display: none; margin: 0 auto; width: 55%; background-color: #e4e4e4" class="well">
                <h3 style="margin-top: 0;">{{ Lang::get('surveys.saveCurrentProject') }}</h3>
                <button class="btn btn-primary" onclick="Survey.saveProject()">{{ Lang::get('buttons.save') }}</button>
                <ul id="saveList"></ul>
                <h3>{{ Lang::get('surveys.savedProjects') }}</h3>
                <div id="loadBox">
                </div>
            </div>
        </div>
        {!! Form::open(['action' => 'SurveyController@store', 'id' => 'createSurveyForm']) !!}
            <div class="form-group">
                <input id="companyId" name="companyId" type="hidden" class="form-control" value="{{ $companyId }}">
            </div>
            <script src="{{ asset('js/survey.global.js') }} "></script>
            <div class="row setup-content" id="step-1">
                <div class="col-xs-12">
                    <div class="col-md-12">
                        @include("survey.partials.step1")
                    </div>
                </div>
            </div>
            <div class="row setup-content" id="step-2" style="display: none;">
                <div class="col-xs-12">
                    <div class="col-md-12">
                        @include("survey.partials.step2")
                    </div>
                </div>
            </div>
            <div class="row setup-content" id="step-3" style="display: none;">
                <div class="col-xs-12">
                    <div class="col-md-12">
                        @include("survey.partials.step3")
                    </div>
                </div>
            </div>
            <div class="row setup-content" id="step-4" style="display: none;">
                <div class="col-xs-12">
                    <div class="col-md-12">
                        @include("survey.partials.step4")
                    </div>
                </div>
            </div>
            <div class="row setup-content" id="step-5" style="display: none;">
                <div class="col-xs-12">
                    <div class="col-md-12">
                        @include("survey.partials.step5")
                    </div>
                </div>
            </div>
            <div class="row setup-content" id="step-6" style="display: none;">
                <div class="col-xs-12">
                    <div class="col-md-12">
                        @include("survey.partials.step6")
                    </div>
                </div>
            </div>
            <div class="row setup-content" id="step-7" style="display: none;">
                <div class="col-xs-12">
                    <div class="col-md-12">
                        @include("survey.partials.step7")
                    </div>
                </div>
            </div>
        {!! Form::close() !!}
    </div>
    <script src="{{ asset('js/helpers.js') }} "></script>
    <script type="text/javascript">
        Survey.languageStrings["validation.email"] = "{{ str_replace(':attribute', 'email field', Lang::get('validation.email')) }}";
        Survey.languageStrings["buttons.finish"] = {!! json_encode(Lang::get('buttons.finish')) !!};
        Survey.languageStrings["buttons.next"] = {!! json_encode(Lang::get('buttons.next')) !!};

        Survey.languageStrings["buttons.load"] = {!! json_encode(Lang::get('buttons.load')) !!};
        Survey.languageStrings["buttons.delete"] = {!! json_encode(Lang::get('buttons.delete')) !!};

        Survey.languageStrings["surveys.individual"] = {!! json_encode(Lang::get('surveys.individualType')) !!};
        Survey.languageStrings["surveys.group"] = {!! json_encode(Lang::get('surveys.groupType')) !!};
        Survey.languageStrings["surveys.progress"] = {!! json_encode(Lang::get('surveys.progressType')) !!};
        Survey.languageStrings["surveys.normal"] = {!! json_encode(Lang::get('surveys.normalType')) !!};
        Survey.languageStrings["surveys.ltt"] = {!! json_encode(Lang::get('surveys.lttType')) !!};
        Survey.languageStrings["surveys.projectSaved"] = {!! json_encode(Lang::get('surveys.projectSaved')) !!};
    </script>
@endsection
