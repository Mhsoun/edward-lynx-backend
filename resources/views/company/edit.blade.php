@extends('layouts.default')
@section('content')
<div class="mainBox">
    <h2> {{$user->name}}</h2>
    @include('errors.list')
    @if (Session::get('changeText') != null)
        <h4>{{ Session::get('changeText') }}</h4>
    @endif

    {!! Form::model($user, ['action' => ['CompanyController@update', $user->id], 'method' => 'put', 'files' => true]) !!}
        <div class="row"><br/>
            <div class="form-group">
                {!! Form::label('name', Lang::get('company.name'), ['class' => 'control-label col-md-2']) !!}
                <div class="col-md-5">
                    {!! Form::text('name', null, ['class'=>'form-control']) !!}
                </div>
            </div>
        </div>
        <br/>

        <div class="row">
            <div class="form-group">
                {!! Form::label('email', Lang::get('company.email'), ['class' => 'control-label col-md-2']) !!}
                <div class="col-md-5">
                    {!! Form::text('email', null, ['class'=>'form-control']) !!}
                </div>
            </div>
        </div>
        <br/>

        <div class="row">
            <div class="form-group">
                {!! Form::label('info', Lang::get('company.otherInfo'), ['class' => 'control-label col-md-2']) !!}
                <div class="col-xs-5">
                    {!! Form::textarea('info', null, ['class'=>'form-control', 'rows' => 5]) !!}
                </div>
            </div>
        </div>
        <br>

        <div class="row">
            <div class="form-group">
                {!! Form::label('survey types', Lang::get('company.surveyTypes'), ['class' => 'control-label col-md-2']) !!}
                <div class="col-xs-5">
                    @foreach (\App\SurveyTypes::all() as $type)
                        <div class="checkbox">
                            <label>
                                <input type="checkbox"
                                       name="surveyType_{{ $type }}"
                                       value="on"
                                       {{ \App\SurveyTypes::canCreate($user->allowedSurveyTypes, $type) ? 'checked' : '' }}>
                                {{ \App\SurveyTypes::name($type) }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <br>

        <div class="row">
            <div class="form-group">
                {!! Form::label('info', Lang::get('company.resetPassword'), ['class' => 'control-label col-md-2']) !!}
                <div class="col-xs-5">
                    <a href="{{ action('CompanyController@resetPasswordView', ['id' => $user->id]) }}">{{ ucfirst(Lang::get('buttons.clickHere')) }}</a>
                </div>
            </div>
        </div>
        <br>

        <div class="row">
            <div class="form-group">
                {!! Form::label('validated', Lang::get('company.licensed'), ['class' => 'control-label col-md-2']) !!}
                <div class="col-xs-5">
                    <div class="checkbox">
                        <label title="{{ !$user->isValidated && $user->password == '' ? Lang::get('company.licensedTooltip') : '' }}">
                            {!! Form::checkbox('validated', 'validated', $user->isValidated) !!}
                            {{ Lang::get('general.yes') }}
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <br>

         <div class="row">
            <div class="form-group">
                {!! Form::label('validated', 'Logo', ['class' => 'control-label col-md-2']) !!}
                    <div class="col-xs-5">
                        <a class="btn btn-primary" href="{{ action('CompanyController@resetLogo', $user->id) }}">{{ Lang::get('buttons.reset') }}</a>
                        <br>
                        <br>

                        <b>{{ Lang::get('company.changeColor') }}</b>
                        {!! Form::text('navColor', $user->navColor, ['class' => 'form-control', 'id' => 'picker', 'style' => 'border-color: #' . $user->navColor]) !!}
                        <br>

                        @if (file_exists('images/logos/' . $user->name . '_logo.png'))
                            <b>{{ Lang::get('company.currentLogo') }}</b>
                            <br>
                            <img style="max-width:100px;" src="{{ asset('images/logos/' . $user->name . '_logo.png') }}" alt="logo">
                            <br>
                            <br>
                        @endif

                        <b>{{ Lang::get('company.selectFileToUpload') }}</b>
                        {!! Form::file('file') !!}
                    </div>
            </div>
        </div>
        <br>

        <div class="form-group">
            <button type="submit" class="btn btn-primary">{{ Lang::get('buttons.update') }}</button>
        </div>

        <a href="{{ action('CompanyController@index') }}">{{ Lang::get('buttons.back') }}</a>
    {!! Form::close() !!}
    <script src="{{ asset('js/colpick.js') }} "></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $('#picker').colpick({
                layout: 'hex',
                submit: 0,
                colorScheme: 'dark',
                color: '#{{ $user->navColor }}',
                onChange: function (hsb, hex, rgb, el, bySetColor) {
                    $(el).css('border-color', '#' + hex);
                    $('#picker').val(hex);
                    if (!bySetColor) {
                        $(el).val(hex);
                    }
                }
            }).keyup(function () {
                $(this).colpickSetColor(this.value);
            });
        });
        </script>
</div>
@stop
